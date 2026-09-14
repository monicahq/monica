<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RelationshipTypePolicy
{
    public function delete(User $user, RelationshipType $relationshipType): Response
    {
        if ($relationshipType->is_system) {
            return Response::deny(__('The type came with your account and stays.'));
        }

        if ($relationshipType->relationshipCount() > 0) {
            return Response::deny(__('Relationships are written with this type, so it stays.'));
        }

        return Response::allow();
    }
}
