<?php

namespace App\Domains\Vault\ManageJournals\Web\Controllers;

use App\Domains\Vault\ManageJournals\Services\CreateSliceOfLife;
use App\Domains\Vault\ManageJournals\Services\DestroySliceOfLife;
use App\Domains\Vault\ManageJournals\Services\UpdateSliceOfLife;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\SliceOfLifeEditViewHelper;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\SliceOfLifeIndexViewHelper;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\SliceOfLifeShowViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Redirect;

class SliceOfLifeController extends Controller
{
    public function index(Request $request, string $vaultId, int $journalId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = $vault->journals()->findOrFail($journalId);

        return Inertia::render('Vault/Journal/Slices/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => SliceOfLifeIndexViewHelper::data($journal),
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
            'name' => $request->input('name'),
        ];

        $slice = (new CreateSliceOfLife)->execute($data);

        return response()->json([
            'data' => SliceOfLifeIndexViewHelper::dtoSlice($slice),
        ], 201);
    }

    public function show(Request $request, string $vaultId, int $journalId, int $sliceOfLifeId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = $vault->journals()->findOrFail($journalId);
        $slice = $journal->slicesOfLife()->findOrFail($sliceOfLifeId);

        return Inertia::render('Vault/Journal/Slices/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => SliceOfLifeShowViewHelper::data($slice),
        ]);
    }

    public function edit(Request $request, string $vaultId, int $journalId, int $sliceOfLifeId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = $vault->journals()->findOrFail($journalId);
        $slice = $journal->slicesOfLife()->findOrFail($sliceOfLifeId);

        return Inertia::render('Vault/Journal/Slices/Edit', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => SliceOfLifeEditViewHelper::data($slice),
        ]);
    }

    public function update(Request $request, string $vaultId, int $journalId, int $sliceOfLifeId)
    {
        $vault = Vault::findOrFail($vaultId);
        $vault->journals()->findOrFail($journalId);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'slice_of_life_id' => $sliceOfLifeId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ];

        $slice = (new UpdateSliceOfLife)->execute($data);

        return Redirect::route('slices.show', [
            'vault' => $vaultId,
            'journal' => $journalId,
            'slice' => $slice->id,
        ]);
    }

    public function destroy(Request $request, string $vaultId, int $journalId, int $sliceOfLifeId)
    {
        $vault = Vault::findOrFail($vaultId);
        $vault->journals()->findOrFail($journalId);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'slice_of_life_id' => $sliceOfLifeId,
        ];

        (new DestroySliceOfLife)->execute($data);

        return Redirect::route('slices.index', [
            'vault' => $vaultId,
            'journal' => $journalId,
        ]);
    }
}
