<?php

namespace App\Domains\Vault\ManageTasks\Web\Controllers;

use App\Domains\Vault\ManageTasks\Web\ViewHelpers\VaultTasksIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VaultTaskController extends Controller
{
    public function index(Request $request, string $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Dashboard/Task/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => VaultTasksIndexViewHelper::data($vault, Auth::user()),
        ]);
    }
}
