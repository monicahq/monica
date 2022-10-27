<?php

namespace App\Vault\ManageVaultSettings\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\Vault\ManageVaultSettings\Services\CreateTag;
use App\Vault\ManageVaultSettings\Services\DestroyTag;
use App\Vault\ManageVaultSettings\Services\UpdateTag;
use App\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsTagController extends Controller
{
    public function store(Request $request, int $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'name' => $request->input('name'),
        ];

        $tag = (new CreateTag())->execute($data);
        $vault = Vault::findOrFail($vaultId);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoTag($vault, $tag),
        ], 201);
    }

    public function update(Request $request, int $vaultId, int $tagId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'tag_id' => $tagId,
            'name' => $request->input('name'),
        ];

        $tag = (new UpdateTag())->execute($data);
        $vault = Vault::findOrFail($vaultId);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoTag($vault, $tag),
        ], 200);
    }

    public function destroy(Request $request, int $vaultId, int $tagId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'tag_id' => $tagId,
        ];

        (new DestroyTag())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
