<?php

namespace App\Jobs\Middleware;

use App\Models\SyncJob;
use App\Enums\JobStatus;
use App\Models\Division;
use App\Models\Employee\Employee;
use App\Models\HealthcareService;
use App\Notifications\SyncNotification;
use Illuminate\Support\Facades\Log;

class PreventDuplicateJobs
{
    /**
     * Prevent duplicate jobs for the same legal entity and entity type.
     *
     * If another job of the same type is already processing or paused for the same legal entity,
     * the current job will be released back to the queue with a delay.
     *
     * @param mixed $job The job instance
     * @param callable $next The next middleware in the pipeline
     * @return mixed
     */
    public function handle($job, $next)
    {
        try {
            // Check if job has the required properties
            if (!property_exists($job, 'legalEntity') || !property_exists($job, 'syncJob')) {
                return $next($job);
            }

            // Get the entity type from the job class name or a dedicated property
            $entityType = $this->getEntityTypeFromJob($job);

            if (!$entityType) {
                return $next($job);
            }

            $isAnotherJobRunning = SyncJob::where('legal_entity_id', $job->legalEntity->id)
                ->where('entity_type', $entityType)
                ->where('id', '!=', $job->syncJob?->id ?? 0)
                ->where(function ($query) {
                    $query->where('status', JobStatus::PROCESSING)
                          ->orWhere('status', JobStatus::PAUSED);
                })
                ->exists();

            if ($isAnotherJobRunning) {
                $jobClass = class_basename($job);

                Log::info("{$jobClass}: Skipping due to another job already running");
                echo "{$jobClass}: Skipping due to another job already running" . PHP_EOL;

                $job->release(10);

                return;
            }

            return $next($job);
        } catch (\Throwable $exception) {
            // Log the middleware error
            Log::error('PreventDuplicateJobs middleware failed', [
                'job' => class_basename($job),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            echo "PreventDuplicateJobs middleware failed for " . class_basename($job) . PHP_EOL;

            $entityName = $this->getEntityNameForNotification($job);

            $job->user->notify(new SyncNotification($entityName, 'failed'));

            // Fail the job so it goes to failed() method
            $job->fail($exception);

            return;
        }
    }

    /**
     * Extract entity type from job instance.
     *
     * @param mixed $job The job instance
     * @return string|null The entity type class name
     */
    private function getEntityTypeFromJob($job): ?string
    {
        // If job has explicit entityType property
        if (property_exists($job, 'entityType')) {
            return $job->entityType;
        }

        // Map job class names to entity types
        $jobClassMap = [
            'SyncDivisionsListJob' => Division::class,
            'SyncHealthcareServicesListJob' => HealthcareService::class,
            'SyncEmployeesListJob' => Employee::class,
            // Add more mappings as needed
        ];

        $jobClassName = class_basename($job);

        return $jobClassMap[$jobClassName] ?? null;
    }

    /**
     * Get entity name for notification based on job type.
     *
     * @param mixed $job The job instance
     * @return string The entity name for notification
     */
    private function getEntityNameForNotification($job): string
    {
        // Map job class names to notification entity names
        $notificationMap = [
            'SyncDivisionsListJob' => 'division',
            'SyncHealthcareServicesListJob' => 'healthcare_service',
            'SyncEmployeesListJob' => 'employee',
            'SyncEmployeeListJob' => 'employee', // Alternative naming
            'SyncCompletedJob' => 'legal_entity',
            // Add more mappings as needed
        ];

        $jobClassName = class_basename($job);

        return $notificationMap[$jobClassName] ?? 'legal_entity'; // Default fallback
    }
}
