<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateVaultDefaultTemplate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsTemplateController extends Controller
{
    public function update(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'template_id' => $request->input('template_id'),
        ];

        (new UpdateVaultDefaultTemplate)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
