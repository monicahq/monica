<?php

namespace App\Domains\Vault\ManageVault\Web\Controllers;

use App\Domains\Contact\ManageLifeEvents\Web\ViewHelpers\ModuleLifeEventViewHelper;
use App\Domains\Vault\ManageLifeMetrics\Web\ViewHelpers\VaultLifeMetricsViewHelper;
use App\Domains\Vault\ManageVault\Services\CreateVault;
use App\Domains\Vault\ManageVault\Services\DestroyVault;
use App\Domains\Vault\ManageVault\Services\UpdateVault;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultCreateViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultEditViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultShowViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VaultController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @see \App\Policies\VaultPolicy
     */
    public function __construct()
    {
        $this->authorizeResource(Vault::class, 'vault');
    }

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

        (new CreateVault)->execute($data);

        return response()->json([
            'data' => route('vault.index'),
        ], 201);
    }

    public function show(Request $request, Vault $vault)
    {
        $contact = Auth::user()->getContactInVault($vault);

        return Inertia::render('Vault/Dashboard/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'lastUpdatedContacts' => VaultShowViewHelper::lastUpdatedContacts($vault),
            'upcomingReminders' => VaultShowViewHelper::upcomingReminders($vault, Auth::user()),
            'favorites' => VaultShowViewHelper::favorites($vault, Auth::user()),
            'dueTasks' => VaultShowViewHelper::dueTasks($vault, Auth::user()),
            'moodTrackingEvents' => VaultShowViewHelper::moodTrackingEvents($vault, Auth::user()),
            'defaultTab' => $vault->default_activity_tab,
            'lifeEvents' => ModuleLifeEventViewHelper::data($contact, Auth::user()),
            'lifeMetrics' => VaultLifeMetricsViewHelper::data($vault, Auth::user(), Carbon::now()->year),
            'url' => [
                'feed' => route('vault.feed.show', [
                    'vault' => $vault,
                ]),
                'default_tab' => route('vault.default_tab.update', [
                    'vault' => $vault,
                ]),
            ],
        ]);
    }

    public function edit(Vault $vault)
    {
        return Inertia::render('Vault/Edit', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => VaultEditViewHelper::data($vault),
        ]);
    }

    public function update(Request $request, Vault $vault)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vault->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        (new UpdateVault)->execute($data);

        return response()->json([
            'data' => route('vault.show', [
                'vault' => $vault,
            ]),
        ], 200);
    }

    public function destroy(Request $request, Vault $vault)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vault->id,
        ];

        (new DestroyVault)->execute($data);

        return response()->json([
            'data' => route('vault.index'),
        ], 200);
    }
}
