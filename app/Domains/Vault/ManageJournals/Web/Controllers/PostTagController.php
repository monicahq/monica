<?php

namespace App\Domains\Vault\ManageJournals\Web\Controllers;

use App\Domains\Vault\ManageJournals\Services\AssignTag;
use App\Domains\Vault\ManageJournals\Services\RemoveTag;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\PostEditViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Vault;
use App\Services\Tags\CreateTagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PostTagController extends Controller
{
    public function store(Request $request, Vault $vault, Journal $journal, Post $post, CreateTagService $service)
    {
        Gate::authorize('vault-editor', $vault);

        Auth::user()->forceFill(['vault_id' => $vault->id]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tag = $service->execute($data);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vault->id,
            'journal_id' => $journal->id,
            'post_id' => $post->id,
            'tag_id' => $tag->id,
        ];

        $tag = (new AssignTag)->execute($data);

        return response()->json([
            'data' => PostEditViewHelper::dtoTag($journal, $post, $tag, true),
        ], 201);
    }

    public function update(Request $request, Vault $vault, Journal $journal, Post $post, Tag $tag)
    {
        Gate::authorize('vault-editor', $vault);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vault->id,
            'journal_id' => $journal->id,
            'post_id' => $post->id,
            'tag_id' => $tag->id,
        ];

        $tag = (new AssignTag)->execute($data);

        return response()->json([
            'data' => PostEditViewHelper::dtoTag($journal, $post, $tag, true),
        ], 200);
    }

    public function destroy(Request $request, Vault $vault, Journal $journal, Post $post, Tag $tag)
    {
        Gate::authorize('vault-editor', $vault);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vault->id,
            'journal_id' => $journal->id,
            'post_id' => $post->id,
            'tag_id' => $tag->id,
        ];

        $tag = (new RemoveTag)->execute($data);

        return response()->json([
            'data' => PostEditViewHelper::dtoTag($journal, $post, $tag, false),
        ], 200);
    }
}
