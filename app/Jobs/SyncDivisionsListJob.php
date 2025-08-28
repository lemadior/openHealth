<?php

namespace App\Jobs;

use App\Livewire\LegalEntity\LegalEntity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Livewire\Division\Api\DivisionRequestApi;

class SyncDivisionsListJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected LegalEntity $legalEntity, protected int $page = 1)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $divisionsList = DivisionRequestApi::syncDivisionRequest($this->legalEntity->uuid, $this->page);

        if (empty($divisionsList)) {
            return;
        }

        foreach($divisionsList as $division) {
            SyncDivisionDetailsJob::dispatch($division);
        }

        if ($this->hasMorePages($divisionsList)) {
        SyncDivisionsListJob::dispatch($this->legalEntity, $this->page + 1);
    }
    }

    // I guess that such way of determine that anoher page exist is simplier
    protected function hasMorePages(array $divisionsList): bool
    {
        return count($divisionsList) === 300; // Here suppose the 300  is max item on the page. It will be corrected further
    }
}
