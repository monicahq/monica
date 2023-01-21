<?php

namespace App\Domains\Vault\ManageVault\Web\Controllers;

use App\Domains\Vault\ManageVault\Services\CreateVault;
use App\Domains\Vault\ManageVault\Services\DestroyVault;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultCreateViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultShowViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VaultController extends Controller
{
    public function index()
    {
        return Inertia::render('Vault/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => VaultIndexViewHelper::data(Auth::user()),
        ]);
    }

    public function create()
    {
        return Inertia::render('Vault/Create', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => VaultCreateViewHelper::data(),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'type' => Vault::TYPE_PERSONAL,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        (new CreateVault())->execute($data);

        return response()->json([
            'data' => route('vault.index'),
        ], 201);
    }

    public function show(Request $request, int $vaultId)
    {
        $vault = Vault::find($vaultId);

        return Inertia::render('Vault/Dashboard/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'lastUpdatedContacts' => VaultShowViewHelper::lastUpdatedContacts($vault),
            'upcomingReminders' => VaultShowViewHelper::upcomingReminders($vault, Auth::user()),
            'favorites' => VaultShowViewHelper::favorites($vault, Auth::user()),
            'dueTasks' => VaultShowViewHelper::dueTasks($vault, Auth::user()),
            'moodTrackingEvents' => VaultShowViewHelper::moodTrackingEvents($vault, Auth::user()),
            'loadFeedUrl' => route('vault.feed.show', [
                'vault' => $vaultId,
            ]),
        ]);
    }

    public function destroy(Request $request, int $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
        ];

        (new DestroyVault())->execute($data);

        return response()->json([
            'data' => route('vault.index'),
        ], 200);
    }
}
