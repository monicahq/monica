<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LifeEventTypePolicy
{
    /**
     * Whether the nature may be removed for good.
     *
     * One the product supplied stays, because the wording it falls back to
     * belongs to the product. A nature a life event has been recorded with will
     * stay too, and that condition joins this one with the Life event record,
     * which does not exist yet.
     */
    public function delete(User $user, LifeEventType $lifeEventType): Response
    {
        if ($lifeEventType->is_system) {
            return Response::deny(__('The type came with your account and stays.'));
        }

        return Response::allow();
    }
}
