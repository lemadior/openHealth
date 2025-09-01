<?php

namespace App\Jobs;

use App\Jobs\Middleware\EnsureUserHasActiveSession;
use App\Notifications\SyncNotification;
use Throwable;
use App\Models\User;
use App\Models\SyncJob;
use App\Enums\JobStatus;
use App\Models\LegalEntity;
use App\Classes\eHealth\EHealth;
use App\Models\HealthcareService;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Repositories\Repository;
use Exception;
use Illuminate\Bus\Batchable;

class SyncHealthcareServicesListJob implements ShouldQueue
{
    use Queueable,
        Batchable;

    public int $tries = 3;
    public int $timeout = 60;

    /** @var int Rate limit delay in seconds (50 requests per minute = 1 request every 1.2s, using 2s for safety) */
    private const int RATE_LIMIT_DELAY = 3;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new EnsureUserHasActiveSession];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $token,
        public User $user,
        protected LegalEntity $legalEntity,
        public ?SyncJob $syncJob = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $page = $this->syncJob?->page ?? 1;
        echo "Queue #" . $page . PHP_EOL;
        $query = '';

        \Log::debug('DIVISIONs LIST JOB:', ['id' => $this, 'user_id' => $this->user->id, 'legal_entity_id' => $this->legalEntity->id, 'page' => $page]);
        $response = null;
        $query = '';

        // TODO: check if all of this JOB works are COMPLETED

        $isAnotherJobShouldStart = SyncJob::where('legal_entity_id', $this->legalEntity->id)
            ->where('entity_type', HealthcareService::class)
            ->where('id', '!=', $this->syncJob?->id ?? 0)
            ->where(function ($query) {
                $query->where('status', JobStatus::PROCESSING)
                      ->orWhere('status', JobStatus::PAUSED);
            })
            ->exists();

        if ($isAnotherJobShouldStart) {
            Log::info('SyncHealthcareServicesListJob: Skipping already processed healthcare services for page ' . $page);
            echo 'SyncHealthcareServicesListJob: Postponed for page ' . $page . PHP_EOL;
            $this->release(10);

            return;
        }

        Log::info('SyncHealthcareServicesListJob: Processing page ' . $page);

        $this->syncJob->markAsProcessing();
        echo "PAGE: ". $page . PHP_EOL;

        try {
            $response = EHealth::healthcareService()
                ->setToken($this->token)
                ->getMany(query: ['page' => $page]);

            echo 'AFT. GET Healthcare Services List Request' . PHP_EOL;

            $healthcareServicesList = $response->validate();

            Repository::healthcareService()->saveHealthcareServiceAll($healthcareServicesList, $this->legalEntity);

        } catch (EHealthResponseException $err) {
            echo 'EHealth Response Error: ' . $err->getCode() . ' - ' . $err->getMessage() . PHP_EOL;

            if (in_array($err->getCode(), [401, 429, 500, 502, 503, 504, 408])) {
                $retryAfter = match($err->getCode()) {
                    401 => 10,  // Unauthorized - maybe token refresh needed
                    429 => 60,  // Rate limit
                    503 => 120, // Service unavailable
                    502, 504 => 30, // Gateway errors
                    500 => 90,  // Internal server error
                    408 => 45,  // Timeout
                    default => 60
                };

                if ($this->attempts() < $this->tries) {
                    echo "Retrying after {$retryAfter} seconds due to error: " . $err->getCode() . PHP_EOL;
                    $this->release($retryAfter);
                } else {
                    echo "Max attempts reached. Not retrying." . PHP_EOL;
                    $this->syncJob->markAsPaused();
                    $this->user->notify(new SyncNotification('legal_entity', 'paused'));
                    $this->batch()?->cancel();
                    throw new Exception('Batch cancelled!');
                }

                return;
            }

            // For other errors, fail immediately
            $this->fail($err);
            return;
        } catch (Throwable $err) {
            echo 'Unexpected error: ' . $err->getMessage() . PHP_EOL;
            $this->fail($err);
            return;
        }

        if (empty($healthcareServicesList)) {
            return;
        }

        echo 'Healthcare Services fetched: ' . count($healthcareServicesList) . PHP_EOL;

        if ($response?->isNotLast()) {
            $newSyncJob = $this->legalEntity->syncJobs()->create([
                'status' => JobStatus::PENDING, // Initial status,
                'entity_type' => HealthcareService::class, // TODO: possible it will be removed or renamed to more obviosly (eg. 'sync')
                'user_id' => $this->user->id,
                'page' => $page + 1,
            ]);

            // Add the next page job to the existing batch
            if ($this->batch()) {
                $nextPageJob = new SyncHealthcareServicesListJob($this->token, $this->user, $this->legalEntity, $newSyncJob);
                // Add delay to next job to respect rate limits
                $nextPageJob->delay(now()->addSeconds(self::RATE_LIMIT_DELAY));
                $this->batch()->add([$nextPageJob]);
                echo "Added page " . ($page + 1) . " to batch with " . self::RATE_LIMIT_DELAY . "s delay" . PHP_EOL;
            } else {
                // Fallback if batch is not available
                SyncHealthcareServicesListJob::dispatch($this->token, $this->user, $this->legalEntity, $newSyncJob)
                    ->delay(now()->addSeconds(self::RATE_LIMIT_DELAY));
                echo "Batch not available, dispatched as separate job with " . self::RATE_LIMIT_DELAY . "s delay" . PHP_EOL;
            }
        } else {
            $this->user->notify(new SyncNotification('healthcare_service', 'completed'));
        }

        $this->syncJob->markAsCompleted();
    }

    public function failed(Exception $exception)
    {
        Log::channel('e_health_errors')->error('Job finally failed after all retries: ', [
            'EXCEPTION' => $exception::class,
            'message' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        echo 'Job finally failed after ' . $this->attempts() . ' attempts. JOB status: ' . $this->syncJob->status->value . PHP_EOL;

        // Mark as failed only if not already marked (e.g., not paused for 401 errors)
        if ($this->syncJob && $this->syncJob->status !== JobStatus::PAUSED) {
            $this->syncJob->markAsFailed();

            $this->user->notify(new SyncNotification('healthcare_service', 'failed'));
        }
    }
}
