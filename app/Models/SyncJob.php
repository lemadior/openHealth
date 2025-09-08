<?php

namespace App\Models;

use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncJob extends Model
{
    protected $fillable = [
        'user_id',
        'legal_entity_id',
        'entity_type',
        'status',
        'page',
        'finished_at'
    ];

    protected $casts = [
        'status' => JobStatus::class,
        'finished_at' => 'datetime'
    ];

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


      public function syncable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsPending(): void
    {
        $this->update(['status' => JobStatus::PENDING]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => JobStatus::FAILED]);
    }

    public function markAsPaused(): void
    {
        $this->update(['status' => JobStatus::PAUSED]);
        $this->refresh();
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => JobStatus::PROCESSING]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => JobStatus::COMPLETED]);
    }

    public function markAsFinished(): void
    {
        $this->update(['finished_at' => now()]);
    }
}
