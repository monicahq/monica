<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ActivityTypePolicy
{
    /**
     * Whether the nature may be removed for good.
     *
     * One the product supplied stays, because the wording it falls back to
     * belongs to the product. A nature an activity has been recorded with will
     * stay too, and that condition joins this one with the Activity record,
     * which does not exist yet.
     */
    public function delete(User $user, ActivityType $activityType): Response
    {
        if ($activityType->is_system) {
            return Response::deny(__('The type came with your account and stays.'));
        }

        return Response::allow();
    }
}
