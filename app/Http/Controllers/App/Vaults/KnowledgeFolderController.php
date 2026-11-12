<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateKnowledgeFolder;
use App\Actions\DestroyKnowledgeFolder;
use App\Actions\UpdateKnowledgeFolder;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeFolder;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KnowledgeFolderController extends Controller
{
    public function create(Request $request, Vault $vault): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        new CreateKnowledgeFolder(
            user: $request->user(),
            vault: $vault,
            name: $request->string('name')->toString(),
        )->execute();

        return redirect()->back()->with('status', __('The folder is added.'));
    }

    public function update(Request $request, Vault $vault, KnowledgeFolder $knowledgeFolder): RedirectResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        new UpdateKnowledgeFolder(
            user: $request->user(),
            folder: $knowledgeFolder,
            name: $request->string('name')->toString(),
        )->execute();

        return redirect()->back()->with('status', __('The folder is renamed.'));
    }

    public function destroy(Request $request, Vault $vault, KnowledgeFolder $knowledgeFolder): RedirectResponse
    {
        $request->validate([
            'destination' => ['required', 'string'],
        ]);

        $destination = $request->string('destination')->toString();
        $moveTo = $destination === 'delete'
            ? null
            : KnowledgeFolder::query()->where('vault_id', $vault->id)->where('uuid', $destination)->firstOrFail();

        try {
            new DestroyKnowledgeFolder(
                user: $request->user(),
                folder: $knowledgeFolder,
                moveTo: $moveTo,
            )->execute();
        } catch (AuthorizationException $e) {
            return redirect()->back()->withErrors(['folder' => $e->getMessage()]);
        }

        return redirect()
            ->route('knowledge.index', $vault)
            ->with('status', __('The folder is deleted.'));
    }
}
