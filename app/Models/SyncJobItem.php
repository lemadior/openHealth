<?php

namespace App\Models;

use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SyncJobItem extends Model
{
    protected $fillable = [
        'sync_job_id',
        'syncable_id',
        'syncable_type',
        'status'
    ];

    protected $casts = [
        'status' => JobStatus::class
    ];

    public function syncJob(): BelongsTo
    {
        return $this->belongsTo(SyncJob::class);
    }

    public function syncable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => JobStatus::Failed->value]);
    }

    public function markAsPaused(): void
    {
        $this->update(['status' => JobStatus::Paused->value]);
    }

    public function markAsProcessed(): void
    {
        $this->update(['status' => JobStatus::Processing->value]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => JobStatus::Completed->value]);
    }
}
