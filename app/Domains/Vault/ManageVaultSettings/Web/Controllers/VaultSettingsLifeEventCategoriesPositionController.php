<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateLifeEventCategoryPosition;
use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsLifeEventCategoriesPositionController extends Controller
{
    public function update(Request $request, string $vaultId, int $lifeEventCategoryId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'life_event_category_id' => $lifeEventCategoryId,
            'new_position' => $request->input('position'),
        ];

        $lifeEventCategory = (new UpdateLifeEventCategoryPosition)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoLifeEventCategory($lifeEventCategory),
        ], 200);
    }
}
