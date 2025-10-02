<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\EHealthJob;
use App\Enums\JobStatus;
use App\Models\Employee\Employee;
use App\Models\User;
use App\Repositories\Repository;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use App\Classes\eHealth\EHealth;
use Illuminate\Support\Facades\Log;
use Throwable;
use GuzzleHttp\Promise\PromiseInterface;
use App\Classes\eHealth\EHealthResponse;
use App\Models\LegalEntity;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\Crypt;

class EmployeeDetailsUpsert extends EHealthJob
{
    use Dispatchable;

    use SerializesModels;

    public const int RATE_LIMIT_DELAY = 1; // seconds

    public const string BATCH_NAME = 'EmployeeDetailsSync';

    public const string SCOPE_REQUIRED = 'employee:details';

    public const string ENTITY = LegalEntity::ENTITY_EMPLOYEE;

    public function backoff(): array
    {
        return [3, 5, 10, 30];
    }


    public function __construct(
        public Employee $employee,
        public ?LegalEntity $legalEntity,
        protected ?EHealthJob $nextEntity = null,
        public bool $standalone = false,
    ) {
        parent::__construct(legalEntity: $legalEntity, nextEntity: $nextEntity, standalone: $standalone);
    }

    // Get data from EHealth API
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse|null
    {
        echo 'Processing EmployeeDetailsUpsert for employee:' . $this->employee->id . ', LE:' . ($this->legalEntity ? $this->legalEntity->id : 'N/A') . PHP_EOL;

        return EHealth::employee()->withToken($this->token)->getDetails($this->employee->uuid, groupByEntities: true);
    }

    // Store or update data in the database
    protected function processResponse(?EHealthResponse $response): void
    {
        $validatedData = $response->validate();

        $this->employee->save();

        Repository::employee()->updateDetails(
            $this->employee,
            $validatedData['party'],
            $validatedData['documents'],
            $validatedData['phones'],
            $validatedData['educations'] ?? null,
            $validatedData['specialities'] ?? null,
            $validatedData['qualifications'] ?? null,
            $validatedData['scienceDegree'] ?? null
        );

        $this->employee->setSyncStatus(JobStatus::COMPLETED);
        $this->employee->refresh();

        $user = $this->employee->party->user;

        if (!$user) {
            Log::info('Employee sync: User does not exist yet for party.', [
                'party_id' => $this->employee->party_id,
                'employee_uuid' => $this->employee->uuid,
            ]);

            return;
        }

        $roleName = $this->employee->employee_type;
        $legalEntityId = $this->employee->legal_entity_id;

        echo "Employee UUID: {$this->employee->uuid}, Role: {$roleName}" . PHP_EOL;

        setPermissionsTeamId($legalEntityId);

        if (!$user->hasRole($roleName)) {
            $user->assignRole($roleName);
        }
    }

      // Handle job failure
    public function failed(?Throwable $exception): void
    {
        // It is need beacuse if job is failed the middleware doesn't called
        $olduser = $this->user ?? ($this->batch()->options['user'] ?? null);
        $token = Crypt::decryptString($this->batch()->options['token'] ?? '');

        // If an error is not raised by the API (code 0), skip the job
        if ($exception->getCode() === 0 && !$exception instanceof MaxAttemptsExceededException && !$exception instanceof ConnectionException) {
            // Log::channel('e_health_errors')->error('Sync job failed: ', [
            //     'EXCEPTION' => $exception::class,
            //     'message' => $exception->getMessage(),
            //     'attempts' => $this->attempts(),
            //     'batch_id' => $this->batch()?->id,
            //     'batch_name' => static::BATCH_NAME,
            //     'user_id' => $olduser?->id,
            // ]);

            $this->logAnError($exception, $olduser?->id ?? null);

            echo "Job FAILED: " . static::BATCH_NAME . " Exception type: " . $exception::class . " Code: " . $exception->getCode() . " Error: " . $exception->getMessage() . PHP_EOL;
            echo "Job has been skipped because of undefined problem. with token: " . $token . PHP_EOL;

            $this->proceedNextJob($olduser, $token);

            return;
        }

        parent::failed($exception);
    }

    /**
     * Get additional middleware configurations for the job.
     *
     * @return array Returns an array of middleware configurations to be applied to the job
     */
    protected function getAdditionalMiddleware(): array
    {
        return [
            new RateLimited('ehealth-employee-get')
        ];
    }

    // Get next entity job if needed
    protected function getNextEntityJob(): ?EHealthJob
    {
        return $this->standalone || !$this->nextEntity
            ? new CompleteSync($this->legalEntity, isFirstLogin: $this->isFirstLogin)
            : $this->nextEntity;
    }
}
