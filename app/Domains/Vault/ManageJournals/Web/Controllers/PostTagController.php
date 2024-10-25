<?php

namespace App\Domains\Vault\ManageJournals\Web\Controllers;

use App\Domains\Vault\ManageJournals\Services\AssignTag;
use App\Domains\Vault\ManageJournals\Services\RemoveTag;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\PostEditViewHelper;
use App\Domains\Vault\ManageVaultSettings\Services\CreateTag;
use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostTagController extends Controller
{
    public function store(Request $request, string $vaultId, int $journalId, int $postId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'name' => $request->input('name'),
        ];

        $tag = (new CreateTag)->execute($data);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
            'tag_id' => $tag->id,
        ];

        $tag = (new AssignTag)->execute($data);
        $journal = Journal::findOrFail($journalId);
        $post = Post::findOrFail($postId);

        return response()->json([
            'data' => PostEditViewHelper::dtoTag($journal, $post, $tag, true),
        ], 201);
    }

    public function update(Request $request, string $vaultId, int $journalId, int $postId, int $tagId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
            'tag_id' => $tagId,
        ];

        $tag = (new AssignTag)->execute($data);
        $journal = Journal::findOrFail($journalId);
        $post = Post::findOrFail($postId);

        return response()->json([
            'data' => PostEditViewHelper::dtoTag($journal, $post, $tag, true),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $journalId, int $postId, int $tagId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
            'tag_id' => $tagId,
        ];

        $tag = (new RemoveTag)->execute($data);

        $journal = Journal::findOrFail($journalId);
        $post = Post::findOrFail($postId);

        return response()->json([
            'data' => PostEditViewHelper::dtoTag($journal, $post, $tag, false),
        ], 200);
    }
}
