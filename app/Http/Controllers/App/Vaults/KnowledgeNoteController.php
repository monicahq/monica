<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateKnowledgeNote;
use App\Actions\DestroyKnowledgeNote;
use App\Actions\UpdateKnowledgeNote;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\Vault;
use App\ViewModels\Vaults\KnowledgeNoteViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeNoteController extends Controller
{
    public function create(Request $request, Vault $vault): RedirectResponse
    {
        $request->validate([
            'folder' => ['nullable', 'uuid'],
        ]);

        $note = new CreateKnowledgeNote(
            user: $request->user(),
            vault: $vault,
            folder: $this->folder($vault, $request->string('folder')->toString()),
        )->execute();

        return redirect()->route('knowledge.notes.edit', [$vault, $note]);
    }

    public function show(Request $request, Vault $vault, KnowledgeNote $knowledgeNote): View
    {
        return view('app.vaults.knowledge.note', [
            'viewModel' => new KnowledgeNoteViewModel(
                user: $request->user(),
                note: $knowledgeNote,
            ),
        ]);
    }

    public function edit(Request $request, Vault $vault, KnowledgeNote $knowledgeNote): View
    {
        return view('app.vaults.knowledge.note', [
            'viewModel' => new KnowledgeNoteViewModel(
                user: $request->user(),
                note: $knowledgeNote,
                editing: true,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, KnowledgeNote $knowledgeNote): RedirectResponse
    {
        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:'.KnowledgeNote::MAX_BODY_LENGTH],
            'folder' => ['nullable', 'uuid'],
        ]);

        new UpdateKnowledgeNote(
            user: $request->user(),
            note: $knowledgeNote,
            title: $request->exists('title') ? $request->string('title')->toString() : null,
            body: $request->exists('body') ? $request->string('body')->toString() : null,
            folder: $this->folder($vault, $request->string('folder')->toString()),
        )->execute();

        return redirect()
            ->route('knowledge.notes.show', [$vault, $knowledgeNote])
            ->with('status', __('The note is saved.'));
    }

    public function destroy(Request $request, Vault $vault, KnowledgeNote $knowledgeNote): RedirectResponse
    {
        new DestroyKnowledgeNote(
            user: $request->user(),
            note: $knowledgeNote,
        )->execute();

        return redirect()
            ->route('knowledge.index', $vault)
            ->with('status', __('The note is deleted.'));
    }

    private function folder(Vault $vault, string $uuid): ?KnowledgeFolder
    {
        if ($uuid === '') {
            return null;
        }

        return KnowledgeFolder::query()->where('vault_id', $vault->id)->where('uuid', $uuid)->firstOrFail();
    }
}
