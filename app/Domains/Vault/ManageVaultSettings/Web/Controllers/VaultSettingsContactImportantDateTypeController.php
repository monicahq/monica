<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultImportantDateTypes\Services\CreateContactImportantDateType;
use App\Domains\Vault\ManageVaultImportantDateTypes\Services\DestroyContactImportantDateType;
use App\Domains\Vault\ManageVaultImportantDateTypes\Services\UpdateContactImportantDateType;
use App\Domains\Vault\ManageVaultImportantDateTypes\Web\ViewHelpers\VaultImportantDateTypesViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsContactImportantDateTypeController extends Controller
{
    public function store(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'label' => $request->input('label'),
            'internal_type' => $request->input('internal_type'),
            'can_be_deleted' => $request->input('can_be_deleted'),
        ];

        $type = (new CreateContactImportantDateType)->execute($data);
        $vault = Vault::findOrFail($vaultId);

        return response()->json([
            'data' => VaultImportantDateTypesViewHelper::dto($type, $vault),
        ], 201);
    }

    public function update(Request $request, string $vaultId, int $typeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_important_date_type_id' => $typeId,
            'label' => $request->input('label'),
            'internal_type' => $request->input('internal_type'),
            'can_be_deleted' => $request->input('can_be_deleted'),
        ];

        $type = (new UpdateContactImportantDateType)->execute($data);
        $vault = Vault::findOrFail($vaultId);

        return response()->json([
            'data' => VaultImportantDateTypesViewHelper::dto($type, $vault),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $typeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_important_date_type_id' => $typeId,
        ];

        (new DestroyContactImportantDateType)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
