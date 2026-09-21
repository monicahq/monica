<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LifeEventCategoryPolicy
{
    public function delete(User $user, LifeEventCategory $lifeEventCategory): Response
    {
        if ($lifeEventCategory->is_system) {
            return Response::deny(__('The category came with your account and stays.'));
        }

        if ($lifeEventCategory->lifeEventTypeCount() > 0) {
            return Response::deny(__('Types are filed under this category, so it stays.'));
        }

        return Response::allow();
    }
}
