<?php

namespace App\Models;

use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncJob extends Model
{
    protected $fillable = [
        'user_id',
        'legal_entity_uuid',
        'status'
    ];

    protected $casts = [
        'status' => JobStatus::class
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SyncJobItem::class);
    }
}
