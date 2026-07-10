<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;

class QueueKick
{
    /**
     * Process queued jobs immediately after the HTTP response
     * so registration emails / ballots don't wait for the Hostinger cron.
     */
    public static function afterResponse(int $maxJobs = 5, int $maxTime = 25): void
    {
        dispatch(function () use ($maxJobs, $maxTime) {
            Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--max-jobs' => $maxJobs,
                '--max-time' => $maxTime,
                '--tries' => 5,
                '--quiet' => true,
            ]);
        })->afterResponse();
    }
}
