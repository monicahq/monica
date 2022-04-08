<?php

namespace App\Vault\ManageVaultSettings\Web\Controllers;

use App\Models\Vault;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Vault\ManageVaultSettings\Services\ChangeVaultAccess;
use App\Vault\ManageVaultSettings\Services\RemoveVaultAccess;
use App\Vault\ManageVaultSettings\Services\GrantVaultAccessToUser;
use App\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;

class VaultSettingsUserController extends Controller
{
    public function store(Request $request, int $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'user_id' => $request->input('user_id'),
            'permission' => $request->input('permission'),
        ];

        $user = (new GrantVaultAccessToUser)->execute($data);

        $vault = Vault::findOrFail($vaultId);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoUser($user, $vault),
        ], 201);
    }

    public function update(Request $request, int $vaultId, int $userId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'user_id' => $userId,
            'permission' => $request->input('permission'),
        ];

        $user = (new ChangeVaultAccess)->execute($data);

        $vault = Vault::findOrFail($vaultId);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoUser($user, $vault),
        ], 200);
    }

    public function destroy(Request $request, int $vaultId, int $userId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'user_id' => $userId,
        ];

        (new RemoveVaultAccess)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
