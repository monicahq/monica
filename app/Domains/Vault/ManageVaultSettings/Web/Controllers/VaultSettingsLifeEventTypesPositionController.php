<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateLifeEventTypePosition;
use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsLifeEventTypesPositionController extends Controller
{
    public function update(Request $request, string $vaultId, int $lifeEventCategoryId, int $lifeEventTypeId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'life_event_category_id' => $lifeEventCategoryId,
            'life_event_type_id' => $lifeEventTypeId,
            'new_position' => $request->input('position'),
        ];

        $lifeEventType = (new UpdateLifeEventTypePosition)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoType($lifeEventType->lifeEventCategory, $lifeEventType),
        ], 200);
    }
}
