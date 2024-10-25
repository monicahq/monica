<?php

namespace App\Domains\Vault\ManageJournals\Web\Controllers;

use App\Domains\Vault\ManageJournals\Services\AddContactToPost;
use App\Domains\Vault\ManageJournals\Services\CreatePost;
use App\Domains\Vault\ManageJournals\Services\DestroyPost;
use App\Domains\Vault\ManageJournals\Services\IncrementPostReadCounter;
use App\Domains\Vault\ManageJournals\Services\UpdatePost;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\PostCreateViewHelper;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\PostEditViewHelper;
use App\Domains\Vault\ManageJournals\Web\ViewHelpers\PostShowViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Helpers\PostHelper;
use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Post;
use App\Models\PostTemplate;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Redirect;

class PostController extends Controller
{
    public function create(Request $request, string $vaultId, int $journalId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = Journal::findOrFail($journalId);

        return Inertia::render('Vault/Journal/Post/Template', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => PostCreateViewHelper::data($journal),
        ]);
    }

    /**
     * The post will be created upon visiting the page.
     * This will create the post as a draft, with all the post sections
     * populated from the post template.
     */
    public function store(Request $request, string $vaultId, int $journalId, int $templateId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = Journal::findOrFail($journalId);

        try {
            PostTemplate::where('account_id', $vault->account_id)
                ->findOrFail($templateId);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('post.choose_template', [
                'vault' => $vaultId,
                'journal' => $journalId,
            ]);
        }

        $post = (new CreatePost)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_template_id' => $templateId,
            'title' => null,
            'published' => false,
            'written_at' => Carbon::now()->format('Y-m-d'),
        ]);

        return redirect()->route('post.edit', [
            'vault' => $vaultId,
            'journal' => $journalId,
            'post' => $post->id,
        ]);
    }

    public function show(Request $request, string $vaultId, int $journalId, int $postId)
    {
        $vault = Vault::findOrFail($vaultId);
        $post = Post::findOrFail($postId);

        (new IncrementPostReadCounter)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
        ]);

        return Inertia::render('Vault/Journal/Post/Show', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => PostShowViewHelper::data($post, Auth::user()),
        ]);
    }

    public function edit(Request $request, string $vaultId, int $journalId, int $postId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = Journal::findOrFail($journalId);
        $post = Post::findOrFail($postId);

        return Inertia::render('Vault/Journal/Post/Edit', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => PostEditViewHelper::data($journal, $post, Auth::user()),
        ]);
    }

    public function update(Request $request, string $vaultId, int $journalId, int $postId)
    {
        Vault::findOrFail($vaultId);

        $post = (new UpdatePost)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
            'title' => $request->input('title'),
            'sections' => $request->input('sections'),
            'written_at' => Carbon::parse($request->input('date'))->format('Y-m-d'),
        ]);

        $post->contacts()->detach();

        if ($request->input('contacts')) {
            if (count($request->input('contacts')) > 0) {
                foreach ($request->input('contacts') as $contact) {
                    $data = [
                        'account_id' => Auth::user()->account_id,
                        'author_id' => Auth::user()->id,
                        'vault_id' => $vaultId,
                        'journal_id' => $journalId,
                        'post_id' => $postId,
                        'contact_id' => $contact['id'],
                    ];

                    (new AddContactToPost)->execute($data);
                }
            }
        }

        return response()->json([
            'data' => PostHelper::statistics($post),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, int $journalId, int $postId)
    {
        (new DestroyPost)->execute([
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'post_id' => $postId,
        ]);

        $journal = Journal::findOrFail($journalId);

        return Redirect::route('journal.show', [
            'vault' => $vaultId,
            'journal' => $journal,
        ]);
    }
}
