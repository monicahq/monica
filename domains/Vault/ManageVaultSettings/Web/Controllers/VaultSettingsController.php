<?php

namespace App\Vault\ManageVaultSettings\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Vault\ManageVaultSettings\Web\ViewHelpers\VaultSettingsIndexViewHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VaultSettingsController extends Controller
{
    public function index(Request $request, int $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Settings/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultSettingsIndexViewHelper::data($vault),
        ]);
    }
}
