<?php

namespace App\Http\Controllers;

use App\Models\OfficialStudentRecord as Record;
use App\Models\Section;
use App\Services\OfficialStudentImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Inertia\Inertia;

class OfficialStudentRecordController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(array_fill_keys(['search', 'department', 'course', 'year_level', 'section'], 'nullable|string|max:150'));
        $removed = $request->routeIs('removed-student-records');
        $query = $removed ? Record::whereNotNull('removed_at') : Record::whereNull('removed_at');
        $headers = array_diff_key(Record::HEADERS, ['status' => true]);
        $courses = \App\Models\Course::query()->whereHas('department');
        if (isset($filters['department'])) {
            $courses->whereHas('department', fn ($query) => $query->where('name', $filters['department']));
        }
        $courseOptions = $courses->orderBy('name')->get(['name', 'duration_years']);
        if (isset($filters['course'])) {
            $courses->where('name', $filters['course']);
        }
        $options = [
            'department' => \App\Models\Department::orderBy('name')->pluck('name'),
            'course' => $courseOptions->pluck('name')->unique()->values(),
            'year_level' => \App\Models\YearLevel::where('sort_order', '<=', $courses->max('duration_years') ?? 0)
                ->orderBy('sort_order')->orderBy('name')->pluck('name'),
        ];
        foreach (array_keys($options) as $field) {
            if (isset($filters[$field])) {
                $query->whereRaw("LOWER(TRIM({$field})) = ?", [mb_strtolower(trim($filters[$field]))]);
            }
        }

        $sections = Section::query()
            ->join('courses', 'courses.id', '=', 'sections.course_id')
            ->join('departments', 'departments.id', '=', 'courses.department_id')
            ->join('year_levels', 'year_levels.id', '=', 'sections.year_level_id');
        foreach (['department' => 'departments.name', 'course' => 'courses.name', 'year_level' => 'year_levels.name'] as $field => $column) {
            if (isset($filters[$field])) {
                $sections->whereRaw("LOWER(TRIM({$column})) = ?", [Record::comparable($filters[$field])]);
            }
        }
        $options['section'] = $sections->distinct()->orderBy('sections.letter')->pluck('sections.letter');

        $section = strtoupper(trim($filters['section'] ?? ''));
        if ($options['section']->contains($section)) {
            $filters['section'] = $section;
            $query->whereIn(DB::raw('UPPER(TRIM(section))'), [$section, 'SECTION '.$section]);
        } else {
            unset($filters['section']);
        }

        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $query->where(function ($query) use ($search, $headers) {
                foreach (array_keys($headers) as $field) {
                    $query->orWhereLike($field, '%'.$search.'%');
                }
            });
        }

        $imports = DB::table('official_student_imports')
            ->select(['id', 'filename', 'administrator_id', 'created_at'])
            ->latest('id')->limit(25)->get();
        $selected = $request->integer('import') ?: ($request->session()->get('official_student_import_id')
            ?? DB::table('official_student_imports')->where(function ($query) {
                $query->where('result->import_status', 'completed')->orWhereNull('result->import_status');
            })->where(function ($query) {
                $query->whereNull('result->mode')->orWhere('result->mode', '!=', 'restore');
            })->whereNull('result->error')->latest('id')->value('id'));
        $batch = $selected ? DB::table('official_student_imports')->where('id', $selected)->first() : null;
        $summary = $batch?->result;

        if ($removed) {
            $headers += ['removed_at' => 'Date Removed', 'removal_reason' => 'Removal Reason'];
            $sort = $request->query('sort') === 'name' ? 'name' : 'date';
            $sort === 'name' ? $query->orderBy('full_name') : $query->latest('removed_at');
            $query->orderBy('student_id');
        } else {
            // Keep each class section together (A, B, C, ...), then show the
            // students in that section alphabetically by the saved full name.
            $query->orderByRaw("CASE WHEN UPPER(TRIM(section)) LIKE 'SECTION %' THEN SUBSTRING(TRIM(section), 9) ELSE UPPER(TRIM(section)) END ASC")
                ->orderByRaw('LOWER(TRIM(full_name)) ASC')
                ->orderBy('student_id');
        }

        $selectionIds = $removed ? collect() : (clone $query)->pluck('id');
        $records = $query->paginate(25)->withQueryString();
        if (! $removed && $records->count()) {
            $history = DB::table('official_student_record_email_audits as audits')
                ->leftJoin('users as administrators', 'administrators.id', '=', 'audits.administrator_id')
                ->whereIn('audits.record_id', $records->getCollection()->pluck('id'))
                ->orderByDesc('audits.created_at')
                ->get([
                    'audits.record_id', 'audits.old_email', 'audits.new_email',
                    'audits.created_at', 'administrators.name as administrator_name',
                ])->groupBy('record_id');
            $records->through(function (Record $record) use ($history) {
                $record->email_updates = $history->get($record->id, collect())->values();
                return $record;
            });
        }

        return Inertia::render('OfficialStudentRecords', [
            'removed' => $removed,
            'sort' => $sort ?? null,
            'latestFullBatch' => $removed ? DB::table('official_student_sync_state')->where('id', 1)->value('latest_full_batch_id') : null,
            'batch' => $selected,
            'unappliedTotal' => $batch ? DB::table('official_student_import_rows')->where('batch_id', $batch->id)
                ->where('category', 'unapplied')->count() : 0,
            'importMetadata' => $batch ? ['filename' => $batch->filename, 'date' => $batch->created_at] : null,
            'preview' => $request->session()->get('official_sync_preview'),
            'records' => $records,
            'selectionIds' => $selectionIds,
            'headers' => $headers,
            'filters' => $filters,
            'options' => $options,
            'imports' => $imports,
            'summary' => $summary ? json_decode($summary, true, flags: JSON_THROW_ON_ERROR) : null,
        ]);
    }

    public function updateEmail(Request $request, Record $record)
    {
        abort_if($record->removed_at !== null, 404);
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $existingVoter = \App\Models\User::query()
            ->where('role', 'voter')->where('student_id_number', $record->student_id)->first();
        $uniqueUserEmail = Rule::unique('users', 'email');
        if ($existingVoter) {
            $uniqueUserEmail->ignore($existingVoter->id);
        }
        $validated = $request->validate([
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('official_student_records', 'email')->ignore($record->id),
                $uniqueUserEmail,
            ],
        ]);

        $oldEmail = $record->email;
        $newEmail = mb_strtolower(trim($validated['email']));
        if (mb_strtolower(trim((string) $oldEmail)) === $newEmail) {
            return back()->with('success', 'No email change was needed.');
        }

        DB::transaction(function () use ($record, $oldEmail, $newEmail, $request) {
            DB::table('official_student_record_email_audits')->insert([
                'record_id' => $record->id,
                'old_email' => $oldEmail,
                'new_email' => $newEmail,
                'administrator_id' => $request->user()->id,
                'created_at' => now(),
            ]);
            $record->forceFill(['email' => $newEmail])->save();
        });

        return back()->with('success', "Official email updated for {$record->full_name}.");
    }

    public function export(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:150',
            'department' => 'nullable|string|max:150',
            'course' => 'nullable|string|max:150',
            'year_level' => 'nullable|string|max:150',
            'section' => 'nullable|string|max:150',
        ]);
        $removed = $request->routeIs('removed-student-records.export');
        $query = $removed ? Record::whereNotNull('removed_at') : Record::whereNull('removed_at');
        foreach (['department', 'course', 'year_level'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->whereRaw("LOWER(TRIM({$field})) = ?", [mb_strtolower(trim($filters[$field]))]);
            }
        }
        if (filled($filters['section'] ?? null)) {
            $section = strtoupper(trim($filters['section']));
            $query->whereIn(DB::raw('UPPER(TRIM(section))'), [$section, 'SECTION '.$section]);
        }
        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                foreach (array_keys(array_diff_key(Record::HEADERS, ['status' => true])) as $field) {
                    $query->orWhereLike($field, '%'.$search.'%');
                }
            });
        }

        $headers = array_diff_key(Record::HEADERS, ['status' => true]);
        if ($removed) {
            $headers += ['removed_at' => 'Date Removed', 'removal_reason' => 'Removal Reason'];
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($removed ? 'Removed Student Records' : 'Official Student Records');
        $sheet->fromArray(array_values($headers), null, 'A1');
        $rowNumber = 2;
        foreach ($query->orderBy('student_id')->cursor() as $record) {
            $values = [];
            foreach (array_keys($headers) as $field) {
                $values[] = $record->{$field};
            }
            $sheet->fromArray($values, null, 'A'.$rowNumber++);
        }
        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $filename = $removed ? 'removed-student-records.xlsx' : 'official-student-records.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(Request $request, OfficialStudentImport $importer, \App\Services\OfficialStudentSync $sync)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'extensions:xlsx,xls,csv'],
            'mode' => 'required|in:add_update,full',
        ], ['file.extensions' => 'Upload an Excel (.xlsx or .xls) or CSV (.csv) file.']);

        $file = $request->file('file');
        [$rows, $summary] = $importer->read($file);
        $filename = $file->getClientOriginalName();
        $admin = $request->user()->id;
        $full = $request->input('mode') === 'full';
        if ($full && $rows && ! $summary['invalid'] && ! isset($summary['error'])) {
            [$revision, $counts] = $sync->preview($rows, $summary, true);
            $token = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Cache::put('official-sync:'.$token, [
                'rows' => $rows, 'summary' => $summary, 'filename' => $filename,
                'revision' => $revision, 'administrator' => $admin,
            ], now()->addMinutes(15));

            return redirect()->route('official-student-records')->with('official_sync_preview', [
                'token' => $token, 'counts' => $counts, 'filename' => $filename,
            ]);
        }

        if ($full) {
            $summary['import_status'] = 'rejected';
            $id = $sync->audit($filename, $admin, $summary + ['mode' => 'full']);
        } else {
            $id = $sync->apply($rows, $summary, false, $filename, $admin);
        }

        $this->notifyNewOfficialRecords($id);

        return redirect()->route('official-student-records', ['import' => $id])->with('official_student_import_id', $id);
    }

    public function confirm(Request $request, \App\Services\OfficialStudentSync $sync)
    {
        $data = $request->validate(['token' => 'required|uuid', 'confirmed' => 'accepted']);
        $key = 'official-sync:'.$data['token'];
        $pending = \Illuminate\Support\Facades\Cache::get($key);
        if (! $pending || $pending['administrator'] !== $request->user()->id) {
            return back()->withErrors(['file' => 'The preview expired. Upload the file again.']);
        }

        $id = $sync->apply($pending['rows'], $pending['summary'], true, $pending['filename'], $pending['administrator'], $pending['revision']);
        \Illuminate\Support\Facades\Cache::forget($key);
        $this->notifyNewOfficialRecords($id);

        return redirect()->route('official-student-records', ['import' => $id])->with('official_student_import_id', $id);
    }

    private function notifyNewOfficialRecords(int $batchId): void
    {
        $summary = DB::table('official_student_imports')->where('id', $batchId)->value('result');
        $result = is_string($summary) ? json_decode($summary, true) : null;
        $added = (int) ($result['added'] ?? 0);

        if ($added < 1 || ($result['import_status'] ?? null) !== 'completed') {
            return;
        }

        app(\App\Services\AdminSystemNotifier::class)->notify(
            'New official student records added',
            $added.' new student '.($added === 1 ? 'record was' : 'records were').' added to the official list.',
            '/official-student-records',
            'official_records_added',
        );
    }

    public function restore(Request $request, int $record, \App\Services\OfficialStudentSync $sync)
    {
        $request->validate(['confirmed' => 'accepted', 'acknowledged' => 'boolean']);
        $sync->restore($record, $request->user()->id, $request->boolean('acknowledged'));

        return back()->with('success', 'Student restored.');
    }

    public function destroy(Request $request, Record $record, \App\Services\OfficialStudentSync $sync)
    {
        $request->validate([
            'confirmation' => ['required', 'in:DELETE'],
        ], [
            'confirmation.required' => 'Type DELETE to confirm removing this student record.',
            'confirmation.in' => 'Type DELETE exactly (all caps) to confirm.',
        ]);

        $sync->remove($record->id, $request->user()->id);

        return back()->with('success', "{$record->full_name} was moved to Removed Student Records.");
    }

    public function destroyMany(Request $request, \App\Services\OfficialStudentSync $sync)
    {
        $validated = $request->validate([
            'confirmation' => ['required', 'in:DELETE'],
            'ids' => ['required', 'array', 'min:1', 'max:10000'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ], [
            'confirmation.required' => 'Type DELETE to confirm removing these student records.',
            'confirmation.in' => 'Type DELETE exactly (all caps) to confirm.',
            'ids.required' => 'Select at least one student record.',
            'ids.min' => 'Select at least one student record.',
        ]);

        $ids = array_map('intval', $validated['ids']);
        $records = Record::query()->whereNull('removed_at')->whereIn('id', $ids)->get(['id']);
        if ($records->count() !== count($ids)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ids' => 'One or more selected records are no longer active. Refresh and try again.',
            ]);
        }

        DB::transaction(function () use ($records, $request, $sync) {
            foreach ($records as $record) {
                $sync->remove($record->id, $request->user()->id);
            }
        }, 3);

        $count = $records->count();
        return back()->with('success', $count === 1
            ? '1 student record was moved to Removed Student Records.'
            : "{$count} student records were moved to Removed Student Records.");
    }

    public function importRows(Request $request, OfficialStudentImport $importer)
    {
        $duplicates = $request->query('category') === 'duplicates';
        $filters = $request->validate([
            'batch' => 'nullable|integer', 'category' => 'nullable|in:added,updated,retained,removed,invalid,duplicates',
            'search' => 'nullable|string|max:150', 'date' => 'nullable|date_format:Y-m-d',
            'department' => 'nullable|string|max:150', 'course' => 'nullable|string|max:150',
        ]);
        $query = DB::table('official_student_import_rows as rows')
            ->join('official_student_imports as batches', 'batches.id', '=', 'rows.batch_id');
        if ($duplicates) {
            $query->where('rows.category', 'duplicates');
        } elseif (isset($filters['category'])) {
            $query->where('rows.category', $filters['category']);
        } else {
            $query->whereNotNull('rows.row_number');
        }
        if (isset($filters['batch'])) {
            $query->where('rows.batch_id', $filters['batch']);
        }
        if (isset($filters['date'])) {
            $query->whereDate('batches.created_at', $filters['date']);
        }
        if (isset($filters['search'])) {
            $query->whereLike('rows.details', '%'.$filters['search'].'%');
        }
        foreach (['department', 'course'] as $field) {
            if (isset($filters[$field])) {
                $query->where('rows.details->values->'.$field, $filters[$field]);
            }
        }

        $catalog = null;
        $rows = $query->orderByDesc('rows.batch_id')->orderBy('rows.row_number')->orderBy('rows.id')
            ->paginate(25, ['rows.*', 'batches.filename', 'batches.created_at'])->withQueryString()
            ->through(function ($row) use ($importer, &$catalog) {
                $row->details = json_decode($row->details, true, flags: JSON_THROW_ON_ERROR);
                if (str_starts_with($row->details['message'] ?? '', 'Use a department name or acronym,')
                    && isset($row->details['values'])) {
                    $reason = $importer->academicError($row->details['values'], $catalog ??= $importer->catalog());
                    if ($reason !== null) {
                        $row->details['message'] = 'Current Settings check. '.$reason;
                    }
                }
                return $row;
            });

        if ($request->expectsJson()) {
            return response()->json($rows);
        }

        return Inertia::render('OfficialImportRows', compact('duplicates', 'filters', 'rows'));
    }

    public function reviewDuplicate(Request $request, int $row)
    {
        $updated = DB::table('official_student_import_rows')->where('id', $row)
            ->where('category', 'duplicates')->whereNull('reviewed_at')
            ->update(['reviewed_at' => now(), 'reviewed_by' => $request->user()->id]);
        abort_unless($updated || DB::table('official_student_import_rows')->where('id', $row)->where('category', 'duplicates')->exists(), 404);

        return back();
    }

}
