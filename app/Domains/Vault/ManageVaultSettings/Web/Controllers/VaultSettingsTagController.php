<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\CreateTag;
use App\Domains\Vault\ManageVaultSettings\Services\DestroyTag;
use App\Domains\Vault\ManageVaultSettings\Services\UpdateTag;
use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsTagController extends Controller
{
    public function store(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'name' => $request->input('name'),
        ];

        $tag = (new CreateTag)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoTag($tag),
        ], 201);
    }

    public function update(Request $request, string $vaultId, int $tagId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'tag_id' => $tagId,
            'name' => $request->input('name'),
        ];

        $tag = (new UpdateTag)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoTag($tag),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $tagId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'tag_id' => $tagId,
        ];

        (new DestroyTag)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
