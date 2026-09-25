<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EntityType;
use App\Models\Field;
use App\Models\User;
use App\Models\Vault;
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

    public function addEntity(User $user, EntityType $entityType, Vault $vault): Response
    {
        if (! $entityType->is_active) {
            return Response::deny(__('This type is turned off. You can view and edit the existing profiles, but you cannot add new ones.'));
        }

        $version = $entityType->defaultVersionIn($vault);

        if ($version === null) {
            return Response::deny(__('Nothing can be added until this vault has a published template for :name.', ['name' => $entityType->name_plural]));
        }

        if ($version->inputFields()->contains(fn (Field $field): bool => $field->is_required && ! $field->canBeFilledIn())) {
            return Response::deny(__('The template for :name requires something that cannot be filled in here yet.', ['name' => $entityType->name_plural]));
        }

        return Response::allow();
    }
}
