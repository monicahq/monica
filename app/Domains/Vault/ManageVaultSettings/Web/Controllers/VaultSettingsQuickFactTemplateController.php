<?php

namespace App\Domains\Vault\ManageVaultSettings\Web\Controllers;

use App\Domains\Vault\ManageVaultSettings\Services\CreateQuickFactTemplate;
use App\Domains\Vault\ManageVaultSettings\Services\DestroyQuickFactTemplate;
use App\Domains\Vault\ManageVaultSettings\Services\UpdateQuickFactTemplate;
use App\Domains\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VaultSettingsQuickFactTemplateController extends Controller
{
    public function store(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'label' => $request->input('label'),
        ];

        $template = (new CreateQuickFactTemplate)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoQuickFactTemplateEntry($template),
        ], 201);
    }

    public function update(Request $request, string $vaultId, int $quickFactTemplateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'vault_quick_facts_template_id' => $quickFactTemplateId,
            'label' => $request->input('label'),
        ];

        $template = (new UpdateQuickFactTemplate)->execute($data);

        return response()->json([
            'data' => VaultSettingsIndexViewHelper::dtoQuickFactTemplateEntry($template),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $quickFactTemplateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'vault_quick_facts_template_id' => $quickFactTemplateId,
        ];

        (new DestroyQuickFactTemplate)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
