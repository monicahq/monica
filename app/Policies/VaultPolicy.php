<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Facades\Gate;

class VaultPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vault $vault): bool
    {
        return Gate::forUser($user)->allows('vault-viewer', $vault);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vault $vault): bool
    {
        return Gate::forUser($user)->allows('vault-manager', $vault);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vault $vault): bool
    {
        return Gate::forUser($user)->allows('vault-editor', $vault);
    }
}
