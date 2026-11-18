<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Vault;
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

        return Response::deny(trans_choice('The free plan holds :count vault, and this account already has it. Full access lifts the ceiling.|The free plan holds :count vaults, and this account already has them. Full access lifts the ceiling.', $limit));
    }

    public function delete(User $user, Vault $vault): Response
    {
        if ($user->shows_vault_list) {
            return Response::allow();
        }

        if (! $vault->is_default) {
            return Response::allow();
        }

        return Response::deny(__('You cannot delete your default vault while the list of vaults is turned off. Turn the list back on in Preferences first.'));
    }
}
