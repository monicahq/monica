<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateVaultListVisibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class MeVaultListController extends Controller
{
    public function update(Request $request): UserResource
    {
        $request->validate([
            'shows_vault_list' => ['required', 'boolean'],
        ]);

        new UpdateVaultListVisibility(
            user: $request->user(),
            showsVaultList: $request->boolean('shows_vault_list'),
        )->execute();

        return new UserResource($request->user()->refresh());
    }
}
