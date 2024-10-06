<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\CreateLifeEventCategory;
use App\Domains\Vault\ManageVaultSettings\Services\DestroyLifeEventCategory;
use App\Domains\Vault\ManageVaultSettings\Services\UpdateLifeEventCategory;
use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsLifeEventCategoriesController extends Controller
{
    public function store(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'label' => $request->input('label'),
            'can_be_deleted' => true,
        ];

        $lifeEventCategory = (new CreateLifeEventCategory)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoLifeEventCategory($lifeEventCategory),
        ], 201);
    }

    public function update(Request $request, string $vaultId, int $lifeEventCategoryId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'life_event_category_id' => $lifeEventCategoryId,
            'label' => $request->input('label'),
            'can_be_deleted' => true,
        ];

        $lifeEventCategory = (new UpdateLifeEventCategory)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoLifeEventCategory($lifeEventCategory),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $lifeEventCategoryId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'life_event_category_id' => $lifeEventCategoryId,
        ];

        (new DestroyLifeEventCategory)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
