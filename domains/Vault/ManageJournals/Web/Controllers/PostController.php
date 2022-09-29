<?php

namespace App\Vault\ManageJournals\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Vault;
use App\Vault\ManageJournals\Services\CreatePost;
use App\Vault\ManageJournals\Web\ViewHelpers\PostCreateViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Redirect;

class PostController extends Controller
{
    public function create(Request $request, int $vaultId, int $journalId)
    {
        $vault = Vault::findOrFail($vaultId);
        $journal = Journal::findOrFail($journalId);

        return Inertia::render('Vault/Journal/Post/Create', [
            'layoutData' => VaultIndexViewHelper::layoutData($vault),
            'data' => PostCreateViewHelper::data($journal),
        ]);
    }

    public function store(Request $request, int $vaultId, int $journalId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'journal_id' => $journalId,
            'content' => $request->input('content'),
            'excerpt' => $request->input('excerpt'),
            'written_at' => $request->input('written_at'),
        ];

        $post = (new CreatePost())->execute($data);

        return Redirect::route('journal.show', [
            'vault' => $post->journal->vault_id,
            'journal' => $post->journal,
        ]);
    }
}
