<?php

namespace App\Domains\Vault\ManageJournals\Web\Controllers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalPhotoIndexViewHelper;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalShowViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class JournalPhotoController extends Controller
{
    public function index(Request $request, string $vaultId, int $journalId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = Journal::findOrFail($journalId);

        return Inertia::render('Vault/Journal/Photo/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => JournalPhotoIndexViewHelper::data($journal),
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
}
