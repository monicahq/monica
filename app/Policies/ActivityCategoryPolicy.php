<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ActivityCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ActivityCategoryPolicy
{
    public function delete(User $user, ActivityCategory $activityCategory): Response
    {
        if ($activityCategory->is_system) {
            return Response::deny(__('The category came with your account and stays.'));
        }

        if ($activityCategory->activityTypeCount() > 0) {
            return Response::deny(__('Types are filed under this category, so it stays.'));
        }

        return Response::allow();
    }
}
