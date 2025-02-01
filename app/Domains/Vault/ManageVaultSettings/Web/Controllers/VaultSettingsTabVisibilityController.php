<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVault\Services\UpdateVaultTabVisibility;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsTabVisibilityController extends Controller
{
    public function update(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'show_group_tab' => $request->boolean('show_group_tab'),
            'show_tasks_tab' => $request->boolean('show_tasks_tab'),
            'show_files_tab' => $request->boolean('show_files_tab'),
            'show_journal_tab' => $request->boolean('show_journal_tab'),
            'show_companies_tab' => $request->boolean('show_companies_tab'),
            'show_reports_tab' => $request->boolean('show_reports_tab'),
            'show_calendar_tab' => $request->boolean('show_calendar_tab'),
        ];

        (new UpdateVaultTabVisibility)->execute($data);
    }
}
