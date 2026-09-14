<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Auth\Access\Response;

class ValueListItemPolicy
{
    public function delete(User $user, ValueListItem $item): Response
    {
        if ($item->usage_count > 0) {
            return Response::deny(__('The value is in use and stays.'));
        }

        return Response::allow();
    }
}
