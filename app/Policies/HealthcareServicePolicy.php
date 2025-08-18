<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\Status;
use App\Models\Division;
use App\Models\LegalEntity;
use App\Models\Employee\Employee;
use App\Models\HealthcareService;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;

class HealthcareServicePolicy
{
    /**
     * User allowed to view the list of HealthscareService
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('healthcare_service:read')) {
            return Response::denyWithStatus(403);
        }

        return Response::allow();
    }

    /**
     * User allow to create the HealthscareService
     */
    public function create(User $user): Response
    {
        if ($user->cannot('healthcare_service:write')) {
            return Response::denyWithStatus(403);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, HealthcareService $healthcareService, ?LegalEntity $legalEntity = null): Response|bool
    {
        // if (is_null($legalEntity)) {
        //     $legalEntity = legalEntity();
        // }

        // // If got null instaed Division model
        // if (empty($division)) {
        //     return Response::denyWithStatus(404);
        // }

        // // Should belong to the same legal entity
        // if ($division->legal_entity_id !== (int) $legalEntity->id) {
        //     return Response::denyWithStatus(404);
        // }

        if ($user->cannot('healthcare_service:write')) {
            return Response::denyWithStatus(403);
        }

        // Inactive divisions cannot be updated
        if ($healthcareService->status === Status::INACTIVE) {
            return Response::deny();
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can activate the division.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Division  $division
     *
     * @return bool
     */
    public function activate(User $user, HealthcareService $healthcareService): Response
    {
        if ($user->cannot('healthcare_service:write')) {
            return Response::denyWithStatus(403);
        }

        // Active divisions cannot be activated
        if ($healthcareService->status === Status::ACTIVE) {
            return Response::deny();
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can deactivate the division.
     *
     * @param  \App\Models\User  $user  The user attempting the action
     * @param  \App\Models\Division  $division  The division to be deactivated
     *
     * @return bool  True if user can deactivate the division, false otherwise
     */
    public function deactivate(User $user, HealthcareService $healthcareService): Response
    {
        if ($user->cannot('healthcare_service:write')) {
            return Response::denyWithStatus(403);
        }

        // // Divisions with at least one active service cannot be deactivated
        // if ($this->hasAnyActiveService($division)) {
        //     return Response::deny();
        // }

        // Divisions that have employees cannot be deactivated
        // if (Employee::where('division_id', $division->id)->exists()) {
        //     return Response::deny();
        // }

        // Inactive divisions cannot be deactivated
        if ($healthcareService->status === Status::INACTIVE) {
            return Response::deny();
        }

        return Response::allow();
    }

    // /**
    //  * Get services associated with a division.
    //  *
    //  * @param Division $division The division to get services for
    //  *
    //  * @return Builder Query builder for division services
    //  */
    // protected function getDivisionServices(Division $division): Builder
    // {
    //     return HealthcareService::query()->where('division_id', $division->id);
    // }

    // /**
    //  * Check if the division has any associated service.
    //  *
    //  * @param  Division  $division  The division to check for services
    //  *
    //  * @return bool  True if the division has at least one service, false otherwise
    //  */
    // protected function hasAnyService(Division $division): bool
    // {
    //     return (bool)$this->getDivisionServices($division)->count();
    // }

    // /**
    //  * Checks if the division has any active service.
    //  *
    //  * @param \App\Models\Division $division The division to check
    //  *
    //  * @return bool Returns true if the division has at least one active service, false otherwise
    //  */
    // protected function hasAnyActiveService(Division $division): bool
    // {
    //     return $this->getDivisionServices($division)
    //         ->where('status', Status::ACTIVE)->exists();

    // }
}
