<?php

namespace App\Domains\Vault\ManageJournals\Web\Controllers;

use App\Domains\Vault\ManageJournals\Services\CreateJournalMetric;
use App\Domains\Vault\ManageJournals\Services\DestroyJournalMetric;
use App\Domains\Vault\ManageJournals\Services\UpdateJournalMetric;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalMetricIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class JournalMetricController extends Controller
{
    public function index(Request $request, string $vaultId, int $journalId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = $vault->journals()->findOrFail($journalId);

        return Inertia::render('Vault/Journal/Metrics/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => JournalMetricIndexViewHelper::data($journal),
        ]);
    }

    public function store(Request $request, string $vaultId, int $journalId)
    {
        $vault = Vault::findOrFail($vaultId);
        $vault->journals()->findOrFail($journalId);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'label' => $request->input('label'),
        ];

        $journalMetric = (new CreateJournalMetric)->execute($data);

        return response()->json([
            'data' => JournalMetricIndexViewHelper::dto($journalMetric),
        ], 201);
    }

    public function update(Request $request, string $vaultId, int $journalId, int $journalMetricId)
    {
        $vault = Vault::findOrFail($vaultId);
        $vault->journals()->findOrFail($journalId);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'journal_metric_id' => $journalMetricId,
            'label' => $request->input('label'),
        ];

        $journalMetric = (new UpdateJournalMetric)->execute($data);

        return response()->json([
            'data' => JournalMetricIndexViewHelper::dto($journalMetric),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $journalId, int $journalMetricId)
    {
        $vault = Vault::findOrFail($vaultId);
        $vault->journals()->findOrFail($journalId);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'journal_metric_id' => $journalMetricId,
        ];

        (new DestroyJournalMetric)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
