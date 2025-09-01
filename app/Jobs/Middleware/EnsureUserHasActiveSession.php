<?php

namespace App\Jobs\Middleware;

use App\Notifications\SyncNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnsureUserHasActiveSession
{
    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {
        // Get user from job instance
        $user = $job->user;

        // Check if user has an active session
        $hasActiveSession = DB::table('sessions')
            ->where('user_id', $user->id)
            ->exists();

        if (!$hasActiveSession) {
            Log::info('EnsureUserHasActiveSession: No active session found for user, deleting job', [
                'user_id' => $user->id,
                'job_class' => get_class($job)
            ]);

            echo "No active session for user {$user->id}, deleting job" . PHP_EOL;

            // Handle sync job - mark as paused and notify user
            $job->syncJob->markAsPaused();
            $user->notify(new SyncNotification($this->getEntityTypeFromJob($job), 'paused'));

            // Cancel batch
            $job->batch()->cancel();

            // Delete job to prevent retries
            $job->delete();
            return;
        }

        Log::debug('EnsureUserHasActiveSession: Active session confirmed for user', [
            'user_id' => $user->id
        ]);

        return $next($job);
    }

    /**
     * Determine entity type from job class name for notifications
     */
    private function getEntityTypeFromJob($job): string
    {
        $className = class_basename($job);

        if (str_contains($className, 'Division')) {
            return 'division';
        } elseif (str_contains($className, 'HealthcareService')) {
            return 'healthcare_service';
        } elseif (str_contains($className, 'Employee')) {
            return 'employee';
        }

        return 'legal_entity';
    }
}
