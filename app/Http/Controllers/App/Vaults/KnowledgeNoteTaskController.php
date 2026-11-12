<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\ToggleKnowledgeNoteTask;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeNote;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KnowledgeNoteTaskController extends Controller
{
    public function update(Request $request, Vault $vault, KnowledgeNote $knowledgeNote): RedirectResponse
    {
        $request->validate([
            'task' => ['required', 'integer', 'min:1'],
            'body_hash' => ['required', 'string'],
        ]);

        new ToggleKnowledgeNoteTask(
            user: $request->user(),
            note: $knowledgeNote,
            task: $request->integer('task'),
            bodyHash: $request->string('body_hash')->toString(),
        )->execute();

        return redirect()->route('knowledge.notes.show', [$vault, $knowledgeNote]);
    }
}
