<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\ValueList;
use Illuminate\Auth\Access\Response;

class ValueListPolicy
{
    public function delete(User $user, ValueList $valueList): Response
    {
        if ($valueList->is_system) {
            return Response::deny(__('This list stays.'));
        }

        return Response::allow();
    }

    public function addItem(User $user, ValueList $valueList): Response
    {
        if (! $valueList->allows_user_values) {
            return Response::deny(__('This value list does not take choices of its own.'));
        }

        return Response::allow();
    }
}
