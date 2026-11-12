<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Location;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LocationPolicy
{
    public function delete(User $user, Location $location): Response
    {
        $count = $location->relationLoaded('children')
            ? $location->children->count()
            : $location->children()->count();

        if ($count > 0) {
            return Response::deny(trans_choice(
                ':count location is inside it. Move it first.|:count locations are inside it. Move them first.',
                $count,
            ));
        }

        $stayCount = $location->stays()->count();

        if ($stayCount > 0) {
            return Response::deny(trans_choice(
                ':count stay of a possession is here. Correct or delete it first.|:count stays of possessions are here. Correct or delete them first.',
                $stayCount,
            ));
        }

        return Response::allow();
    }
}
