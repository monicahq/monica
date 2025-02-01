<?php

namespace App\Domains\Vault\ManageVault\Web\Controllers;

use App\Domains\Vault\ManageVault\Services\UpdateVaultDashboardDefaultTab;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultDefaultTabOnDashboardController extends Controller
{
    public function update(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'default_activity_tab' => $request->input('default_activity_tab'),
        ];

        (new UpdateVaultDashboardDefaultTab)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
