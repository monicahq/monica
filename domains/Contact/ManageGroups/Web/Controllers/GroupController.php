<?php

namespace App\Contact\ManageGroups\Web\Controllers;

use App\Contact\ManageGroups\Web\ViewHelpers\GroupShowViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Vault;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index(Request $request, int $vaultId)
    {
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
