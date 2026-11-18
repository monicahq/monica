<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Preferences;

use App\Actions\UpdateVaultListVisibility;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VaultListController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'shows_vault_list' => ['required', 'boolean'],
        ]);

        $showsVaultList = $request->boolean('shows_vault_list');

        new UpdateVaultListVisibility(
            user: $request->user(),
            showsVaultList: $showsVaultList,
        )->execute();

        return redirect()->route('settings.preferences.index')
            ->with('status', $showsVaultList ? __('The list of vaults is shown again.') : __('You now go straight to your default vault.'));
    }
}
