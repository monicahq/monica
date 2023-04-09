<?php

namespace App\Domains\Vault\ManageVault\Web\Controllers;

use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultReminderIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VaultReminderController extends Controller
{
    public function index(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Dashboard/Reminder/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultReminderIndexViewHelper::data($vault, Auth::user()),
        ]);
    }
}
