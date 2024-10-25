<?php

namespace App\Domains\Vault\ManageJournals\Web\Controllers;

use App\Domains\Vault\ManageJournals\Services\AddPostToSliceOfLife;
use App\Domains\Vault\ManageJournals\Services\RemovePostFromSliceOfLife;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\SliceOfLifeShowViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostSliceOfLifeController extends Controller
{
    public function update(Request $request, string $vaultId, int $journalId, int $postId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = $vault->journals()->findOrFail($journalId);
        $journal->posts()->findOrFail($postId);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
            'slice_of_life_id' => $request->input('slice_of_life_id'),
        ];

        $slice = (new AddPostToSliceOfLife)->execute($data);

        return response()->json([
            'data' => SliceOfLifeShowViewHelper::dtoSlice($slice),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $journalId, int $postId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = $vault->journals()->findOrFail($journalId);
        $journal->posts()->findOrFail($postId);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
        ];

        (new RemovePostFromSliceOfLife)->execute($data);

        return response()->json([
            'data' => null,
        ], 200);
    }
}
