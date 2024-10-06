<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\CreateLifeEventType;
use App\Domains\Vault\ManageVaultSettings\Services\DestroyLifeEventType;
use App\Domains\Vault\ManageVaultSettings\Services\UpdateLifeEventType;
use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsLifeEventTypesController extends Controller
{
    public function store(Request $request, string $vaultId, int $lifeEventCategoryId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'life_event_category_id' => $lifeEventCategoryId,
            'label' => $request->input('label'),
            'can_be_deleted' => true,
        ];

        $lifeEventType = (new CreateLifeEventType)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoType($lifeEventType->lifeEventCategory, $lifeEventType),
        ], 201);
    }

    public function update(Request $request, string $vaultId, int $lifeEventCategoryId, int $lifeEventTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'life_event_category_id' => $lifeEventCategoryId,
            'life_event_type_id' => $lifeEventTypeId,
            'label' => $request->input('label'),
            'can_be_deleted' => true,
        ];

        $lifeEventType = (new UpdateLifeEventType)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoType($lifeEventType->lifeEventCategory, $lifeEventType),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $lifeEventCategoryId, int $lifeEventTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'life_event_category_id' => $lifeEventCategoryId,
            'life_event_type_id' => $lifeEventTypeId,
        ];

        (new DestroyLifeEventType)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
