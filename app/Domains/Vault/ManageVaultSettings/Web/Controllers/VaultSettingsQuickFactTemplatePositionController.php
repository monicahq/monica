<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateQuickFactTemplatePosition;
use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsQuickFactTemplatePositionController extends Controller
{
    public function update(Request $request, string $vaultId, int $quickFactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'vault_quick_facts_template_id' => $quickFactId,
            'new_position' => $request->input('position'),
        ];

        $parameter = (new UpdateQuickFactTemplatePosition)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoQuickFactTemplateEntry($parameter),
        ], 200);
    }
}
