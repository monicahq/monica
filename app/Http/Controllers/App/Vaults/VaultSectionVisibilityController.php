<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\UpdateVaultSectionVisibility;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VaultSectionVisibilityController extends Controller
{
    public function update(Request $request, Vault $vault): RedirectResponse
    {
        $request->validate([
            'shows_people' => ['nullable', 'boolean'],
            'shows_things' => ['nullable', 'boolean'],
            'shows_knowledge' => ['nullable', 'boolean'],
        ]);

        new UpdateVaultSectionVisibility(
            user: $request->user(),
            vault: $vault,
            showsPeople: $request->boolean('shows_people'),
            showsThings: $request->boolean('shows_things'),
            showsKnowledge: $request->boolean('shows_knowledge'),
        )->execute();

        return redirect()->route('vaults.settings.index', $vault)
            ->with('status', __('The vault is saved.'));
    }
}
