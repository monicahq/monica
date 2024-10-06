<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\ChangeVaultAccess;
use App\Domains\Vault\ManageVaultSettings\Services\GrantVaultAccessToUser;
use App\Domains\Vault\ManageVaultSettings\Services\RemoveVaultAccess;
use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsUserController extends Controller
{
    public function store(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
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

    public function update(Request $request, string $vaultId, string $userId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
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

    public function destroy(Request $request, string $vaultId, string $userId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'user_id' => $userId,
        ];

        (new RemoveVaultAccess)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
