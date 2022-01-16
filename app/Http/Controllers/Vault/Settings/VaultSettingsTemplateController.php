<?php

namespace App\Http\Controllers\Vault\Settings;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Vault\ManageVault\UpdateVaultDefaultTemplate;

class VaultSettingsTemplateController extends Controller
{
    public function update(Request $request, int $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'template_id' => $request->input('template_id'),
        ];

        (new UpdateVaultDefaultTemplate)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
