<?php

namespace App\Domains\Contact\ManageGroups\Web\Controllers;

use App\Domains\Contact\ManageGroups\Services\DestroyGroup;
use App\Domains\Contact\ManageGroups\Services\UpdateGroup;
use App\Domains\Contact\ManageGroups\Web\ViewHelpers\GroupEditViewHelper;
use App\Domains\Contact\ManageGroups\Web\ViewHelpers\GroupIndexViewHelper;
use App\Domains\Contact\ManageGroups\Web\ViewHelpers\GroupShowViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(Request $request, string $vaultId): Response
    {
        $vault = Vault::findOrFail($vaultId);

        return Inertia::render('Vault/Group/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => GroupIndexViewHelper::data($vault),
        ]);
    }

    public function show(Request $request, string $vaultId, int $groupId): Response
    {
        $vault = Vault::findOrFail($vaultId);
        $group = Group::with([
            'contacts',
            'groupType',
        ])->findOrFail($groupId);

        return Inertia::render('Vault/Group/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => GroupShowViewHelper::data($group),
        ]);
    }

    public function edit(Request $request, string $vaultId, int $groupId): Response
    {
        Gate::authorize('vault-editor', $vaultId);

        $vault = Vault::findOrFail($vaultId);
        $group = Group::with([
            'contacts',
            'groupType',
        ])->findOrFail($groupId);

        return Inertia::render('Vault/Group/Edit', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => GroupEditViewHelper::data($group),
        ]);
    }

    public function update(Request $request, string $vaultId, string $groupId)
    {
        Gate::authorize('vault-editor', $vaultId);

        $data = [
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'group_id' => $groupId,
            'group_type_id' => $request->input('group_type_id'),
            'name' => $request->input('name'),
        ];

        $group = (new UpdateGroup)->execute($data);

        return response()->json([
            'data' => route('group.show', [
                'vault' => $vaultId,
                'group' => $group,
            ]),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $groupId): JsonResponse
    {
        Gate::authorize('vault-editor', $vaultId);

        $data = [
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'group_id' => $groupId,
        ];

        (new DestroyGroup)->execute($data);

        return response()->json([
            'data' => route('group.index', [
                'vault' => $vaultId,
            ]),
        ], 200);
    }
}
