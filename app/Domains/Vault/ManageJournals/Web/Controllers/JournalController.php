<?php

namespace App\Domains\Vault\ManageJournals\Web\Controllers;

use App\Domains\Vault\ManageJournals\Services\CreateJournal;
use App\Domains\Vault\ManageJournals\Services\DestroyJournal;
use App\Domains\Vault\ManageJournals\Services\UpdateJournal;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalCreateViewHelper;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalEditViewHelper;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalIndexViewHelper;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalShowViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Redirect;

class JournalController extends Controller
{
    public function index(Request $request, Vault $vault)
    {
        return Inertia::render('Vault/Journal/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => JournalIndexViewHelper::data($vault, Auth::user()),
        ]);
    }

    public function create(Request $request, Vault $vault)
    {
        Gate::authorize('vault-editor', $vault);

        return Inertia::render('Vault/Journal/Create', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => JournalCreateViewHelper::data($vault),
        ]);
    }

    public function store(Request $request, string $vaultId)
    {
        Gate::authorize('vault-editor', $vaultId);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        $journal = (new CreateJournal)->execute($data);

        return Redirect::route('journal.show', [
            'vault' => $vaultId,
            'journal' => $journal,
        ]);
    }

    public function show(Request $request, string $vaultId, int $journalId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = Journal::findOrFail($journalId);

        return Inertia::render('Vault/Journal/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => JournalShowViewHelper::data($journal, Carbon::now()->year, Auth::user()),
        ]);
    }

    public function year(Request $request, string $vaultId, int $journalId, int $year)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = Journal::findOrFail($journalId);

        return Inertia::render('Vault/Journal/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => JournalShowViewHelper::data($journal, $year, Auth::user()),
        ]);
    }

    public function edit(Request $request, string $vaultId, int $journalId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = Journal::findOrFail($journalId);

        return Inertia::render('Vault/Journal/Edit', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => JournalEditViewHelper::data($vault, $journal),
        ]);
    }

    public function update(Request $request, string $vaultId, int $journalId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        $journal = (new UpdateJournal)->execute($data);

        return Redirect::route('journal.show', [
            'vault' => $vaultId,
            'journal' => $journal,
        ]);
    }

    public function destroy(Request $request, string $vaultId, int $journalId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
        ];

        (new DestroyJournal)->execute($data);

        return Redirect::route('journal.index', [
            'vault' => $vaultId,
        ]);
    }
}
