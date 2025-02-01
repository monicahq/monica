<?php

namespace App\Domains\Vault\ManageJournals\Web\Controllers;

use App\Domains\Vault\ManageJournals\Services\CreatePostMetric;
use App\Domains\Vault\ManageJournals\Services\DestroyPostMetric;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\PostEditViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostMetricController extends Controller
{
    public function store(Request $request, string $vaultId, int $journalId, int $postId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
            'journal_metric_id' => $request->input('journal_metric_id'),
            'value' => $request->input('value'),
            'label' => $request->input('label'),
        ];

        $postMetric = (new CreatePostMetric)->execute($data);

        return response()->json([
            'data' => PostEditViewHelper::dtoPostMetric($postMetric),
        ], 201);
    }

    public function destroy(Request $request, string $vaultId, int $journalId, int $postId, int $postMetricId)
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
            'post_metric_id' => $postMetricId,
        ];

        (new DestroyPostMetric)->execute($data);

        return response()->json([
            'data' => null,
        ], 200);
    }
}
