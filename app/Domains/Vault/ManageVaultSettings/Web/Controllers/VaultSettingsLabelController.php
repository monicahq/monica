<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\CreateLabel;
use App\Domains\Vault\ManageVaultSettings\Services\DestroyLabel;
use App\Domains\Vault\ManageVaultSettings\Services\UpdateLabel;
use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsLabelController extends Controller
{
    public function store(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'bg_color' => $request->input('bg_color'),
            'text_color' => $request->input('text_color'),
        ];

        $label = (new CreateLabel)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoLabel($label),
        ], 201);
    }

    public function update(Request $request, string $vaultId, int $labelId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'label_id' => $labelId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'bg_color' => $request->input('bg_color'),
            'text_color' => $request->input('text_color'),
        ];

        $label = (new UpdateLabel)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoLabel($label),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $labelId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'label_id' => $labelId,
        ];

        (new DestroyLabel)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
