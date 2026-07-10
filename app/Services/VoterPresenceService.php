<?php

namespace App\Services;

use App\Models\User;
use App\Models\VoterPresence;
use App\Models\VoterPresenceSnapshot;
use Illuminate\Support\Carbon;

class VoterPresenceService
{
    public function heartbeat(User $user, string $device): void
    {
        if ($user->role !== 'voter') {
            return;
        }

        $device = $device === VoterPresence::DEVICE_MOBILE
            ? VoterPresence::DEVICE_MOBILE
            : VoterPresence::DEVICE_DESKTOP;

        VoterPresence::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'device' => $device,
                'last_seen_at' => now(),
            ],
        );
    }

    public function metrics(): array
    {
        $eligibleVoters = User::query()
            ->where('role', 'voter')
            ->where('is_verified', true)
            ->count();

        $activeVoters = User::query()
            ->where('role', 'voter')
            ->where('is_verified', true)
            ->where('registration_status', User::STATUS_ACTIVE)
            ->count();

        $expiredAccounts = User::query()
            ->where('role', 'voter')
            ->where(function ($query) {
                $query->where('registration_status', User::STATUS_EXPIRED)
                    ->orWhere(function ($inner) {
                        $inner->whereNotNull('account_expires_at')
                            ->where('account_expires_at', '<=', now());
                    });
            })
            ->count();

        $online = VoterPresence::onlineQuery()
            ->selectRaw('device, count(*) as total')
            ->groupBy('device')
            ->pluck('total', 'device');

        $onlineMobile = (int) ($online[VoterPresence::DEVICE_MOBILE] ?? 0);
        $onlineDesktop = (int) ($online[VoterPresence::DEVICE_DESKTOP] ?? 0);

        return [
            'active_voters' => $activeVoters,
            'voters_online' => $onlineMobile + $onlineDesktop,
            'eligible_voters' => $eligibleVoters,
            'expired_accounts' => $expiredAccounts,
            'online_mobile' => $onlineMobile,
            'online_desktop' => $onlineDesktop,
        ];
    }

    public function recordSnapshotIfNeeded(): void
    {
        $metrics = $this->metrics();
        $latest = VoterPresenceSnapshot::query()->latest('recorded_at')->first();

        if ($latest && $latest->recorded_at->gt(now()->subMinute())) {
            return;
        }

        VoterPresenceSnapshot::create([
            'recorded_at' => now(),
            'online_total' => $metrics['voters_online'],
            'online_mobile' => $metrics['online_mobile'],
            'online_desktop' => $metrics['online_desktop'],
        ]);

        // Keep roughly 8 days of minute-level history.
        VoterPresenceSnapshot::query()
            ->where('recorded_at', '<', now()->subDays(8))
            ->delete();
    }

    public function chartSeries(string $range = '24h'): array
    {
        $this->recordSnapshotIfNeeded();

        [$from, $bucketMinutes] = match ($range) {
            '1h' => [now()->subHour(), 1],
            '7d' => [now()->subDays(7), 60],
            default => [now()->subDay(), 5],
        };

        $rows = VoterPresenceSnapshot::query()
            ->where('recorded_at', '>=', $from)
            ->orderBy('recorded_at')
            ->get(['recorded_at', 'online_mobile', 'online_desktop']);

        if ($rows->isEmpty()) {
            $metrics = $this->metrics();

            return [[
                'date' => now()->toIso8601String(),
                'label' => now()->format('g:i A'),
                'mobile' => $metrics['online_mobile'],
                'desktop' => $metrics['online_desktop'],
            ]];
        }

        if ($bucketMinutes <= 1) {
            return $rows->map(fn (VoterPresenceSnapshot $row) => [
                'date' => $row->recorded_at->toIso8601String(),
                'label' => $row->recorded_at->format('g:i A'),
                'mobile' => $row->online_mobile,
                'desktop' => $row->online_desktop,
            ])->values()->all();
        }

        return $rows
            ->groupBy(function (VoterPresenceSnapshot $row) use ($bucketMinutes) {
                $ts = $row->recorded_at->copy()->second(0);
                $minute = (int) floor($ts->minute / $bucketMinutes) * $bucketMinutes;
                $ts->minute($minute);

                return $ts->format('Y-m-d H:i');
            })
            ->map(function ($group, string $key) use ($range) {
                /** @var VoterPresenceSnapshot $last */
                $last = $group->last();
                $at = Carbon::createFromFormat('Y-m-d H:i', $key);

                return [
                    'date' => $at->toIso8601String(),
                    'label' => $range === '7d'
                        ? $at->format('M j g:i A')
                        : $at->format('g:i A'),
                    'mobile' => $last->online_mobile,
                    'desktop' => $last->online_desktop,
                ];
            })
            ->values()
            ->all();
    }
}
