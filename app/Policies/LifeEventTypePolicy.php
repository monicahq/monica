<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LifeEventTypePolicy
{
    /**
     * Whether the nature may be removed for good.
     *
     * One the product supplied stays, because the wording it falls back to
     * belongs to the product. One a life event has been recorded with stays
     * too, since that life event is still drawn and edited with it.
     */
    public function delete(User $user, LifeEventType $lifeEventType): Response
    {
        if ($lifeEventType->is_system) {
            return Response::deny(__('The type came with your account and stays.'));
        }

        if (LifeEvent::query()->where('life_event_type_id', $lifeEventType->id)->exists()) {
            return Response::deny(__('Life events were recorded with this type, so it stays.'));
        }

        return Response::allow();
    }
}
