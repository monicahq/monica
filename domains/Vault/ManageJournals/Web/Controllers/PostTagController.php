<?php

namespace App\Vault\ManageJournals\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Post;
use App\Vault\ManageJournals\Services\AssignTag;
use App\Vault\ManageJournals\Services\RemoveTag;
use App\Vault\ManageJournals\Web\ViewHelpers\PostEditViewHelper;
use App\Vault\ManageVaultSettings\Services\CreateTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostTagController extends Controller
{
    public function store(Request $request, int $vaultId, int $journalId, int $postId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'name' => $request->input('name'),
        ];

        $tag = (new CreateTag())->execute($data);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
            'tag_id' => $tag->id,
        ];

        $tag = (new AssignTag())->execute($data);
        $journal = Journal::findOrFail($journalId);
        $post = Post::findOrFail($postId);

        return response()->json([
            'data' => PostEditViewHelper::dtoTag($journal, $post, $tag, true),
        ], 201);
    }

    public function update(Request $request, int $vaultId, int $journalId, int $postId, int $tagId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
            'tag_id' => $tagId,
        ];

        $tag = (new AssignTag())->execute($data);
        $journal = Journal::findOrFail($journalId);
        $post = Post::findOrFail($postId);

        return response()->json([
            'data' => PostEditViewHelper::dtoTag($journal, $post, $tag, true),
        ], 200);
    }

    public function destroy(Request $request, int $vaultId, int $journalId, int $postId, int $tagId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
            'tag_id' => $tagId,
        ];

        $tag = (new RemoveTag())->execute($data);

        $journal = Journal::findOrFail($journalId);
        $post = Post::findOrFail($postId);

        return response()->json([
            'data' => PostEditViewHelper::dtoTag($journal, $post, $tag, false),
        ], 200);
    }
}
