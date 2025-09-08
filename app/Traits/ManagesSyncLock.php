<?php

namespace App\Traits;

use App\Models\User;
use App\Models\LegalEntity;

trait ManagesSyncLock
{
    /**
     * Release the sync lock for a specific user and legal entity
     *
     * @param User $user
     * @param LegalEntity $legalEntity
     * @param string $reason
     * @return void
     */
    protected function releaseSyncLock(User $user, LegalEntity $legalEntity, string $reason = ''): void
    {
        $lockKey = "sync_in_progress:{$user->id}:{$legalEntity->id}";
        cache()->forget($lockKey);
        
        $reasonText = $reason ? " due to {$reason}" : '';
        echo "Released sync lock{$reasonText} for user {$user->id}" . PHP_EOL;
    }

    /**
     * Get the sync lock key for a user and legal entity
     *
     * @param User $user
     * @param LegalEntity $legalEntity
     * @return string
     */
    protected function getSyncLockKey(User $user, LegalEntity $legalEntity): string
    {
        return "sync_in_progress:{$user->id}:{$legalEntity->id}";
    }

    /**
     * Check if sync lock exists for a user and legal entity
     *
     * @param User $user
     * @param LegalEntity $legalEntity
     * @return bool
     */
    protected function hasSyncLock(User $user, LegalEntity $legalEntity): bool
    {
        $lockKey = $this->getSyncLockKey($user, $legalEntity);
        return cache()->has($lockKey);
    }
}
