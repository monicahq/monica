<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EntityType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EntityTypePolicy
{
    public function delete(User $user, EntityType $entityType): Response
    {
        if ($entityType->entity_count > 0) {
            return Response::deny(__('The type still holds records and stays.'));
        }

        return Response::allow();
    }
}
