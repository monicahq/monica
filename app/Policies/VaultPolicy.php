<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class VaultPolicy
{
    public function create(User $user): Response
    {
        $limit = $user->account->vaultLimit();

        if ($limit === null) {
            return Response::allow();
        }

        if ($user->account->vaults()->count() < $limit) {
            return Response::allow();
        }

        return Response::deny(trans_choice('The free offer holds :count vault, and this account already has it. Full access lifts the ceiling.|The free offer holds :count vaults, and this account already has them. Full access lifts the ceiling.', $limit));
    }
}
