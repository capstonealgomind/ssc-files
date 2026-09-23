<?php

namespace App\Http\Controllers;

use App\Models\BallotReceipt;
use App\Models\Department;
use App\Models\Election;
use App\Models\SchoolYearSetting;
use App\Models\User;
use App\Services\FraudDetectionService;
use App\Services\OcrService;
use App\Support\NameLetters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VoterController extends Controller
{
    public function index(): Response
    {
        $openElections = $this->openElections();
        $votedByUser = $this->votedElectionIdsByUser($openElections);

        $voterModels = User::where('role', 'voter')
            ->with(['department:id,name,color', 'course:id,name', 'yearLevel:id,name,sort_order'])
            ->orderByDesc('created_at')
            ->get();

        $voters = $voterModels
            ->map(fn (User $u) => $this->summarize($u, $openElections, $votedByUser))
            ->values()
            ->all();

        return Inertia::render('Voters', [
            'voters' => $voters,
            'identicalNameCount' => count($this->identicalNamePairs($voterModels)),
            'activeVoting' => [
                'is_open' => $openElections->isNotEmpty(),
                'elections' => $openElections->map(fn (Election $e) => [
                    'id' => $e->id,
                    'title' => $e->title,
                ])->values()->all(),
            ],
        ]);
    }

    public function identicalNames(): Response
    {
        $voters = User::where('role', 'voter')
            ->with(['department:id,name', 'course:id,name', 'yearLevel:id,name'])
            ->orderBy('created_at')
            ->get();

        return Inertia::render('IdenticalNames', [
            'pairs' => $this->identicalNamePairs($voters),
        ]);
    }

    public function flagIdenticalName(User $voter): RedirectResponse
    {
        abort_if($voter->role !== 'voter', 404);

        $voter->forceFill([
            'identical_name_flagged' => true,
            'is_verified' => false,
            'verified_at' => null,
        ])->save();

        return back()->with('success', "{$voter->name} was flagged. This account cannot vote.");
    }

    public function unflagIdenticalName(User $voter): RedirectResponse
    {
        abort_if($voter->role !== 'voter', 404);

        $voter->forceFill([
            'identical_name_flagged' => false,
        ])->save();

        return back()->with('success', "The flag was removed from {$voter->name}. Verify the account again before they can vote.");
    }

    public function show(User $voter): Response
    {
        abort_if($voter->role !== 'voter', 404);

        $voter->load(['department:id,name,color', 'course:id,name', 'yearLevel:id,name,sort_order']);

        return Inertia::render('VoterDetail', [
            'voter' => $this->detail($voter),
            'nextRiskVoter' => $this->nextHighCriticalVoter($voter),
            'riskQueueRemaining' => $this->highCriticalRemainingAfter($voter),
        ]);
    }

    public function exists(User $voter): JsonResponse
    {
        abort_if($voter->role !== 'voter', 404);

        return response()->json(['exists' => true]);
    }

    public function verify(User $voter): RedirectResponse
    {
        abort_if($voter->role !== 'voter', 404);

        if ($voter->isIdenticalNameFlagged()) {
            return back()->with('error', 'Remove the identical-name flag before approving this account.');
        }

        $startYear = (int) SchoolYearSetting::current()->start_year;

        $voter->update([
            'is_verified' => true,
            'verified_at' => $voter->verified_at ?? now(),
            'year_level_updated_school_year_start' => $startYear > 0
                ? $startYear
                : $voter->year_level_updated_school_year_start,
        ]);
        $voter->load(['course', 'yearLevel']);
        $voter->applyCourseExpiry();

        return back()->with('success', "{$voter->name} has been verified successfully.");
    }

    public function rerunOcr(User $voter): RedirectResponse
    {
        abort_if($voter->role !== 'voter', 404);

        if (!$voter->id_image_path) {
            return back()->withErrors(['ocr' => 'No ID image on file for this voter.']);
        }

        $imagePath = Storage::disk('public')->path($voter->id_image_path);
        $ocr       = app(OcrService::class)->extract($imagePath);

        if (!$ocr->available) {
            return back()->with('error', 'OCR could not extract text from the saved ID image.');
        }

        $voter->update([
            'ocr_name'       => $ocr->name       ?? $voter->ocr_name,
            'ocr_student_id' => $ocr->studentId  ?? $voter->ocr_student_id,
            'ocr_course'     => $ocr->course      ?? $voter->ocr_course,
        ]);

        // Recalculate fraud score
        $breakdown = app(FraudDetectionService::class)->calculate($voter->fresh(), [
            'device_fingerprint' => '',
            'image_quality'      => $voter->fresh()->image_quality ?? 'good',
        ]);
        $voter->update(['fraud_score' => $breakdown->total]);

        return back()->with('success', 'OCR re-processed. Name: '.($ocr->name ?? '—').', Student ID: '.($ocr->studentId ?? '—'));
    }

    public function reject(User $voter): RedirectResponse
    {
        abort_if($voter->role !== 'voter', 404);

        $voter->update([
            'is_verified' => false,
            'verified_at' => null,
        ]);

        return back()->with('success', "{$voter->name}'s verification has been removed.");
    }

    public function destroy(Request $request, User $voter): RedirectResponse
    {
        abort_if($voter->role !== 'voter', 404);

        $this->validateDeleteConfirmation($request);

        $name = $voter->name;
        $this->purgeVoter($voter);

        return redirect()
            ->route('voters')
            ->with('success', "{$name} has been permanently deleted.");
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $this->validateDeleteConfirmation($request);

        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['integer', 'distinct'],
        ], [
            'ids.required' => 'Select at least one student to delete.',
            'ids.min'      => 'Select at least one student to delete.',
        ]);

        $ids = array_values(array_unique($validated['ids']));

        $voters = User::query()
            ->where('role', 'voter')
            ->whereIn('id', $ids)
            ->get();

        if ($voters->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'ids' => 'One or more selected students could not be deleted. Refresh and try again.',
            ]);
        }

        $count = $voters->count();

        DB::transaction(function () use ($voters) {
            foreach ($voters as $voter) {
                $this->purgeVoter($voter);
            }
        });

        return redirect()
            ->route('voters')
            ->with('success', $count === 1
                ? '1 student has been permanently deleted.'
                : "{$count} students have been permanently deleted.");
    }

    private function validateDeleteConfirmation(Request $request): void
    {
        $request->validate([
            'confirmation' => ['required', 'in:DELETE'],
        ], [
            'confirmation.required' => 'Type DELETE to confirm permanent deletion.',
            'confirmation.in'       => 'Type DELETE exactly (all caps) to confirm.',
        ]);
    }

    private function purgeVoter(User $voter): void
    {
        if ($voter->id_image_path) {
            Storage::disk('public')->delete($voter->id_image_path);
        }

        if ($voter->profile_photo_path) {
            Storage::disk('public')->delete($voter->profile_photo_path);
        }

        $voter->delete();
    }

    // ── Formatters ────────────────────────────────────────────────────────

    /**
     * HIGH = fraud_score 20–49, CRITICAL = fraud_score < 20.
     * Next button only applies while reviewing those queues.
     *
     * @return array{id: int, name: string}|null
     */
    private function nextHighCriticalVoter(User $current): ?array
    {
        if (($current->fraud_score ?? 0) >= 50) {
            return null;
        }

        $ids = $this->highCriticalVoterIds();
        $index = $ids->search($current->id);

        if ($index === false) {
            return null;
        }

        $nextId = $ids->get($index + 1);
        if (! $nextId) {
            return null;
        }

        $next = User::query()->find($nextId, ['id', 'name']);

        return $next
            ? ['id' => $next->id, 'name' => $next->name]
            : null;
    }

    private function highCriticalRemainingAfter(User $current): int
    {
        if (($current->fraud_score ?? 0) >= 50) {
            return 0;
        }

        $ids = $this->highCriticalVoterIds();
        $index = $ids->search($current->id);

        if ($index === false) {
            return 0;
        }

        return max($ids->count() - ((int) $index) - 1, 0);
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function highCriticalVoterIds()
    {
        return User::query()
            ->where('role', 'voter')
            ->where('fraud_score', '<', 50)
            ->orderBy('fraud_score')
            ->orderBy('id')
            ->pluck('id');
    }

    /**
     * Elections that are currently accepting votes.
     *
     * @return Collection<int, Election>
     */
    private function openElections(): Collection
    {
        return Election::query()
            ->votingOpen()
            ->orderBy('title')
            ->get(['id', 'title', 'status', 'voting_starts_at', 'voting_ends_at']);
    }

    /**
     * @param  Collection<int, Election>  $openElections
     * @return Collection<int, Collection<int, int>>
     */
    private function votedElectionIdsByUser(Collection $openElections): Collection
    {
        if ($openElections->isEmpty()) {
            return collect();
        }

        return BallotReceipt::query()
            ->whereIn('election_id', $openElections->pluck('id'))
            ->get(['user_id', 'election_id'])
            ->groupBy('user_id')
            ->map(fn (Collection $rows) => $rows->pluck('election_id')->unique()->values());
    }

    /**
     * @param  Collection<int, Election>  $openElections
     * @param  Collection<int, Collection<int, int>>  $votedByUser
     * @return array{
     *     status: 'voted'|'not_voted'|null,
     *     voted_elections: list<array{id: int, title: string}>,
     *     unvoted_elections: list<array{id: int, title: string}>
     * }
     */
    private function votingStatusFor(
        User $u,
        Collection $openElections,
        Collection $votedByUser,
    ): array {
        if ($openElections->isEmpty()) {
            return [
                'status' => null,
                'voted_elections' => [],
                'unvoted_elections' => [],
            ];
        }

        $votedIds = collect($votedByUser->get($u->id, collect()))->map(fn ($id) => (int) $id)->all();

        $voted = $openElections
            ->filter(fn (Election $e) => in_array((int) $e->id, $votedIds, true))
            ->map(fn (Election $e) => ['id' => $e->id, 'title' => $e->title])
            ->values()
            ->all();

        $unvoted = $openElections
            ->reject(fn (Election $e) => in_array((int) $e->id, $votedIds, true))
            ->map(fn (Election $e) => ['id' => $e->id, 'title' => $e->title])
            ->values()
            ->all();

        $eligible = $u->is_verified && ! $u->isExpired() && ! $u->isDisabled() && ! $u->isIdenticalNameFlagged();

        return [
            'status' => $eligible
                ? (count($unvoted) === 0 ? 'voted' : 'not_voted')
                : null,
            'eligible' => $eligible,
            'voted_elections' => $voted,
            'unvoted_elections' => $unvoted,
        ];
    }

    private function summarize(
        User $u,
        ?Collection $openElections = null,
        ?Collection $votedByUser = null,
    ): array {
        $openElections ??= collect();
        $votedByUser ??= collect();

        return [
            'id'                  => $u->id,
            'name'                => $u->name,
            'email'               => $u->email,
            'voter_id_number'     => $u->voter_id_number,
            'student_id_number'   => $u->student_id_number,
            'department_id'       => $u->department_id,
            'department'          => $u->department?->name,
            'department_color_hex'=> $u->department
                ? Department::colorHex($u->department->color)
                : null,
            'course_id'           => $u->course_id,
            'course'              => $u->course?->name,
            'year_level_id'       => $u->year_level_id,
            'year_level'          => $u->yearLevel?->name,
            'year_level_sort'     => $u->yearLevel?->sort_order,
            'school_year_updated' => $u->hasUpdatedYearLevelThisSchoolYear(),
            'school_year_label'   => SchoolYearSetting::current()->label(),
            'fraud_score'         => FraudDetectionService::syncVerificationScore($u),
            'is_verified'         => $u->is_verified,
            'verified_at'         => $u->verified_at?->format('M j, Y g:i A'),
            'email_verified'      => (bool) $u->email_verified_at,
            'is_expired'          => $u->isExpired(),
            'is_disabled'         => $u->isDisabled(),
            'identical_name_flagged' => $u->isIdenticalNameFlagged(),
            'account_expires_at'  => $u->account_expires_at?->format('M d, Y'),
            'registration_status' => $u->registration_status,
            'profile_photo_url'   => $u->profilePhotoUrl(),
            'created_at'          => $u->created_at->toDateTimeString(),
            'voting'              => $this->votingStatusFor($u, $openElections, $votedByUser),
        ];
    }

    private function detail(User $u): array
    {
        $openElections = $this->openElections();
        $votedByUser = $this->votedElectionIdsByUser($openElections);
        $base = $this->summarize($u, $openElections, $votedByUser);

        return array_merge($base, [
            'id_image_url'         => $u->id_image_path ? asset('storage/'.$u->id_image_path) : null,
            'ocr_name'             => $u->ocr_name,
            'ocr_student_id'       => $u->ocr_student_id,
            'ocr_course'           => $u->ocr_course,
            'ocr_name_match'       => FraudDetectionService::namesMatch($u->ocr_name, $u->name),
            'ocr_student_id_match' => FraudDetectionService::studentIdMatches($u->ocr_student_id, $u->student_id_number),
            'ocr_course_match'     => FraudDetectionService::courseMatches($u->ocr_course, $u->course?->name),
            'ocr_available'          => (bool) $u->ocr_name || (bool) $u->ocr_student_id,
            'image_quality'          => $u->image_quality,
            'email_name_match'       => FraudDetectionService::emailMatchesName($u->email, $u->ocr_name ?? $u->name),
            'registered_at'          => $u->created_at->format('M j, Y g:i A'),
        ]);
    }

    /**
     * @param  Collection<int, User>  $voters
     * @return list<array{distance: int, existing: array<string, mixed>, newer: array<string, mixed>}>
     */
    private function identicalNamePairs(Collection $voters): array
    {
        $list = $voters->values();
        $count = $list->count();
        $pairs = [];

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                /** @var User $first */
                $first = $list[$i];
                /** @var User $second */
                $second = $list[$j];
                $distance = NameLetters::nearDuplicateDistance($first->name, $second->name);

                if ($distance === null) {
                    continue;
                }

                $firstIsNewer = ($first->created_at?->gt($second->created_at) ?? false)
                    || ($first->created_at?->equalTo($second->created_at) && $first->id > $second->id);
                $existing = $firstIsNewer ? $second : $first;
                $newer = $firstIsNewer ? $first : $second;

                $pairs[] = [
                    'distance' => $distance,
                    'existing' => $this->identicalNameCard($existing),
                    'newer' => $this->identicalNameCard($newer),
                ];
            }
        }

        usort($pairs, function (array $left, array $right): int {
            return $left['distance'] <=> $right['distance']
                ?: strcmp($left['newer']['name'], $right['newer']['name']);
        });

        return $pairs;
    }

    /**
     * @return array<string, mixed>
     */
    private function identicalNameCard(User $voter): array
    {
        return [
            'id' => $voter->id,
            'name' => $voter->name,
            'email' => $voter->email,
            'voter_id_number' => $voter->voter_id_number,
            'student_id_number' => $voter->student_id_number,
            'department' => $voter->department?->name,
            'course' => $voter->course?->name,
            'year_level' => $voter->yearLevel?->name,
            'is_verified' => (bool) $voter->is_verified,
            'is_disabled' => $voter->isDisabled(),
            'identical_name_flagged' => $voter->isIdenticalNameFlagged(),
            'profile_photo_url' => $voter->profilePhotoUrl(),
            'registered_at' => $voter->created_at?->format('M j, Y g:i A'),
        ];
    }
}
