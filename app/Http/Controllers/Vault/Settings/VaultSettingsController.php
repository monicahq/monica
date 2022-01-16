<?php

namespace App\Http\Controllers\Vault\Settings;

use Inertia\Inertia;
use App\Models\Vault;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Vault\Settings\ViewHelpers\VaultSettingsIndexViewHelper;

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
