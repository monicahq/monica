<?php

namespace App\Vault\ManageTasks\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\Vault\ManageTasks\Web\ViewHelpers\VaultTasksIndexViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VaultTaskController extends Controller
{
    public function index(Request $request, int $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Dashboard/Task/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultTasksIndexViewHelper::data($vault, Auth::user()),
        ]);
    }
}
