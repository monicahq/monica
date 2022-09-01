<?php

namespace App\Vault\ManageVault\Web\Controllers;

use App\Contact\ManageContactFeed\Web\ViewHelpers\ModuleFeedViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Vault;
use App\Vault\ManageVault\Services\CreateVault;
use App\Vault\ManageVault\Services\DestroyVault;
use App\Vault\ManageVault\Web\ViewHelpers\VaultCreateViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultShowViewHelper;
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
            'author_id' => Auth::user()->id,
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

        $contactIds = Contact::where('vault_id', $vaultId)->select('id')->get()->toArray();

        $items = ContactFeedItem::whereIn('contact_id', $contactIds)
            ->with('author')
            ->with('contact')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Vault/Dashboard/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'lastUpdatedContacts' => VaultShowViewHelper::lastUpdatedContacts($vault),
            'upcomingReminders' => VaultShowViewHelper::upcomingReminders($vault, Auth::user()),
            'favorites' => VaultShowViewHelper::favorites($vault, Auth::user()),
            'feed' => ModuleFeedViewHelper::data($items, Auth::user()),
            'dueTasks' => VaultShowViewHelper::dueTasks($vault, Auth::user()),
        ]);
    }

    public function destroy(Request $request, int $vaultId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
        ];

        (new DestroyVault())->execute($data);

        return response()->json([
            'data' => route('vault.index'),
        ], 200);
    }
}
