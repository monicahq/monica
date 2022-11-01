<?php

namespace App\Domains\Contact\ManageGroups\Web\Controllers;

use App\Domains\Contact\ManageGroups\Web\ViewHelpers\GroupIndexViewHelper;
use App\Domains\Contact\ManageGroups\Web\ViewHelpers\GroupShowViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index(Request $request, int $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Group/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => GroupIndexViewHelper::data($vault),
        ]);
    }

    public function show(Request $request, int $vaultId, int $groupId)
    {
        $vault = Vault::findOrFail($vaultId);
        $group = Group::with([
            'contacts',
            'groupType',
        ])->findOrFail($groupId);

        return Inertia::render('Vault/Group/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => GroupShowViewHelper::data($group, Auth::user()),
        ]);
    }
}
