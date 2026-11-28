<?php

namespace App\Services;

use App\Models\OfficialStudentRecord as Record;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfficialStudentSync
{
    public function preview(array $rows, array $summary, bool $full): array
    {
        return DB::transaction(function () use ($rows, $summary, $full) {
            $state = $this->state()->lockForUpdate()->first();
            [$counts] = $this->plan($rows, $summary, $full);

            unset($counts['results'], $counts['row_numbers'], $counts['issues']);
            return [$state->revision, $counts];
        }, 5);
    }

    public function apply(array $rows, array $summary, bool $full, string $filename, int $administrator, ?int $revision = null): int
    {
        return DB::transaction(function () use ($rows, $summary, $full, $filename, $administrator, $revision) {
            $state = $this->state()->lockForUpdate()->first();
            if ($full && ($revision === null || (int) $state->revision !== $revision)) {
                throw ValidationException::withMessages(['file' => 'Official records changed after preview. Upload the file again to review the new totals.']);
            }
            if (! $rows || ($full && $summary['invalid']) || isset($summary['error'])) {
                $summary['import_status'] = 'rejected';
                return $this->audit($filename, $administrator, $summary + ['mode' => $full ? 'full' : 'add_update']);
            }

            [$counts, $changes, $missing] = $this->plan($rows, $summary, $full);
            if ($full && $counts['invalid'] > 0) {
                $counts['import_status'] = 'rejected';

                return $this->audit($filename, $administrator, $counts + ['mode' => 'full']);
            }

            $now = now();
            foreach ($counts['results'] ?? [] as $index => $result) {
                if ($result['category'] === 'removed') {
                    $counts['results'][$index]['removed_at'] = $now->toDateTimeString();
                    $counts['results'][$index]['message'] = 'Absent from the authoritative import.';
                }
            }
            $batch = $this->audit($filename, $administrator, $counts + [
                'mode' => $full ? 'full' : 'add_update',
                'removed_student_ids' => array_keys($missing), 'import_status' => 'completed',
            ]);

            $new = [];
            foreach ($changes as [$existing, $values]) {
                $values['last_import_batch_id'] = $batch;
                if ($full) {
                    $values['last_full_batch_id'] = $batch;
                }
                if (! $existing) {
                    $new[] = $values + ['email' => null, 'created_at' => $now, 'updated_at' => $now];
                    continue;
                }
                if ($existing->removed_at !== null) {
                    $values += ['removed_at' => null, 'removal_reason' => null, 'restored_at' => $now];
                }
                $oldEmail = $existing->email;
                $newEmail = $values['email'] ?? $oldEmail;
                if ($full && $existing->removed_at === null && array_diff_assoc(array_intersect_key($values, Record::HEADERS), $existing->getAttributes()) === []) {
                    DB::table('official_student_records')->where('id', $existing->id)->update(['last_full_batch_id' => $batch]);
                    continue;
                }
                Record::whereKey($existing->id)->update($values);
                if ($newEmail !== null && mb_strtolower(trim((string) $oldEmail)) !== mb_strtolower(trim((string) $newEmail))) {
                    DB::table('official_student_record_email_audits')->insert([
                        'record_id' => $existing->id,
                        'old_email' => $oldEmail,
                        'new_email' => $newEmail,
                        'administrator_id' => $administrator,
                        'created_at' => $now,
                    ]);
                }
            }
            foreach (array_chunk($new, 250) as $chunk) {
                Record::insert($chunk);
            }
            foreach (array_chunk(array_values($missing), 500) as $ids) {
                Record::whereIn('id', $ids)->update([
                    'removed_at' => $now,
                    'removal_reason' => 'Absent from authoritative import #'.$batch,
                    'last_import_batch_id' => $batch,
                ]);
            }

            $this->state()->update(['revision' => $state->revision + 1]
                + ($full ? ['latest_full_batch_id' => $batch] : []));

            return $batch;
        }, 5);
    }

    public function restore(int $id, int $administrator, bool $acknowledged): void
    {
        DB::transaction(function () use ($id, $administrator, $acknowledged) {
            $state = $this->state()->lockForUpdate()->first();
            $record = Record::whereNotNull('removed_at')->findOrFail($id);
            $absent = $state->latest_full_batch_id !== null
                && (int) $record->last_full_batch_id !== (int) $state->latest_full_batch_id;
            if ($absent && ! $acknowledged) {
                throw ValidationException::withMessages(['restore' => 'Confirm that this student is absent from the latest authoritative list.']);
            }

            Record::whereKey($id)->update(['removed_at' => null, 'removal_reason' => null, 'restored_at' => now()]);
            $this->audit('Restore '.$record->student_id, $administrator, [
                'mode' => 'restore', 'student_id' => $record->student_id,
                'previous_removed_at' => $record->removed_at, 'previous_reason' => $record->removal_reason,
                'absent_from_latest_list' => $absent,
            ]);
            $this->state()->increment('revision');
        }, 5);
    }

    public function remove(int $id, int $administrator): void
    {
        DB::transaction(function () use ($id, $administrator) {
            $state = $this->state()->lockForUpdate()->first();
            $record = Record::query()->whereNull('removed_at')->lockForUpdate()->findOrFail($id);
            $now = now();
            $snapshot = array_intersect_key($record->getAttributes(), Record::HEADERS);
            $batch = $this->audit('Manual removal '.$record->student_id, $administrator, [
                'mode' => 'manual_remove',
                'removed' => 1,
                'results' => [[
                    'row' => null,
                    'category' => 'removed',
                    'values' => $snapshot,
                    'message' => 'Manually removed by administrator.',
                ]],
            ]);
            $record->forceFill([
                'removed_at' => $now,
                'removal_reason' => 'Manually removed by administrator #'.$administrator,
                'last_import_batch_id' => $batch,
            ])->save();
            $this->state()->update(['revision' => $state->revision + 1]);
        }, 5);
    }

    private function plan(array $rows, array $summary, bool $full): array
    {
        // Key by the normalized letter/digit sequence so legacy records saved
        // with spaces or different dash characters still update the same row.
        $stored = Record::get()->toBase()->keyBy(fn (Record $record) => Record::studentId($record->student_id));
        $activeNames = [];
        foreach ($stored as $storedId => $record) {
            if ($record->removed_at === null) {
                $activeNames[Record::nameKey($record->full_name)][] = $storedId;
            }
        }
        $counts = $summary + ['updated' => 0, 'retained' => 0, 'removed' => 0, 'reactivated' => 0];
        $changes = $missing = [];

        foreach ($rows as $id => $values) {
            $existing = $stored->get($id);
            $nameKey = Record::nameKey($values['full_name']);
            $conflictingIds = array_values(array_filter(
                $activeNames[$nameKey] ?? [],
                fn (string $storedId) => $storedId !== (string) $id,
            ));
            if ($conflictingIds !== []) {
                $conflict = $stored->get($conflictingIds[0]);
                $counts['duplicates']++;
                $counts['invalid']++;
                $counts['results'][] = [
                    'row' => $summary['row_numbers'][$id] ?? null,
                    'category' => 'invalid',
                    'values' => $values,
                    'message' => 'Duplicate student name already exists in the official records (Student ID '.$conflict->student_id.').',
                    'first_values' => array_intersect_key($conflict->getAttributes(), Record::HEADERS),
                ];
                continue;
            }

            $syncValues = $values;
            if ($existing && ($syncValues['email'] ?? null) === null) {
                unset($syncValues['email']);
            }
            $category = match (true) {
                ! $existing => 'added',
                $existing->removed_at !== null => 'reactivated',
                array_diff_assoc($syncValues, $existing->getAttributes()) !== [] => 'updated',
                default => 'duplicates',
            };
            $counts[$category]++;
            $result = [
                'row' => $summary['row_numbers'][$id],
                'category' => $category === 'reactivated' ? 'updated' : $category,
                'values' => $values,
            ];
            if ($category === 'duplicates') {
                $result['message'] = 'Student ID, name and details match an existing official record.';
                $counts['results'][] = $result;
                if (! $full) {
                    continue;
                }
            } else {
                if (in_array($category, ['updated', 'reactivated'], true)) {
                    $result['previous'] = array_intersect_key($existing->getAttributes(), $syncValues);
                }
                $counts['results'][] = $result;
            }
            $changes[] = [$existing, $syncValues];
        }

        foreach ($stored->except(array_keys($rows)) as $id => $record) {
            if ($record->removed_at !== null) {
                continue;
            }
            if ($full) {
                $missing[$id] = $record->id;
                $counts['results'][] = ['row' => null, 'category' => 'removed',
                    'values' => array_intersect_key($record->getAttributes(), Record::HEADERS)];
            }
        }
        $counts['removed'] = count($missing);

        return [$counts, $changes, $missing];
    }

    private function state()
    {
        return DB::table('official_student_sync_state')->where('id', 1);
    }

    public function audit(string $filename, int $administrator, array $summary): int
    {
        $details = array_merge($summary['issues'] ?? [], $summary['results'] ?? []);
        if (isset($summary['error']) || ($summary['import_status'] ?? null) === 'rejected') {
            $classified = array_column($details, 'row');
            foreach ($summary['row_numbers'] ?? [] as $row) {
                if (! in_array($row, $classified, true)) {
                    $details[] = ['row' => $row, 'category' => 'unapplied', 'message' => 'Import rejected.'];
                }
            }
        }
        unset($summary['results'], $summary['row_numbers']);
        $summary['import_status'] ??= isset($summary['error']) ? 'rejected' : 'completed';
        return DB::transaction(function () use ($filename, $administrator, $summary, $details) {
        $batch = DB::table('official_student_imports')->insertGetId([
            'administrator_id' => $administrator,
            'filename' => mb_substr($filename, 0, 255),
            'result' => json_encode($summary, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE),
            'created_at' => now(),
        ]);
        foreach (array_chunk($details, 250) as $chunk) {
            DB::table('official_student_import_rows')->insert(array_map(fn ($detail) => [
                'batch_id' => $batch, 'row_number' => $detail['row'] ?? null,
                'category' => $detail['category'],
                'details' => json_encode($detail, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE),
            ], $chunk));
        }

        return $batch;
        });
    }
}
