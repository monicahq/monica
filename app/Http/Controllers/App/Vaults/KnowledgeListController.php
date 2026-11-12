<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateKnowledgeList;
use App\Actions\DestroyKnowledgeList;
use App\Actions\UpdateKnowledgeList;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\Vault;
use App\ViewModels\Vaults\KnowledgeListViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeListController extends Controller
{
    public function create(Request $request, Vault $vault): RedirectResponse
    {
        $request->validate([
            'folder' => ['nullable', 'uuid'],
        ]);

        $list = new CreateKnowledgeList(
            user: $request->user(),
            vault: $vault,
            folder: $this->folder($vault, $request->string('folder')->toString()),
        )->execute();

        return redirect()->route('knowledge.lists.show', [$vault, $list]);
    }

    public function show(Request $request, Vault $vault, KnowledgeList $knowledgeList): View
    {
        return view('app.vaults.knowledge.list', [
            'viewModel' => new KnowledgeListViewModel(
                user: $request->user(),
                list: $knowledgeList,
            ),
        ]);
    }

    public function edit(Request $request, Vault $vault, KnowledgeList $knowledgeList): View
    {
        return view('app.vaults.knowledge.list', [
            'viewModel' => new KnowledgeListViewModel(
                user: $request->user(),
                list: $knowledgeList,
                editing: true,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, KnowledgeList $knowledgeList): RedirectResponse
    {
        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:'.KnowledgeList::MAX_DESCRIPTION_LENGTH],
            'folder' => ['nullable', 'uuid'],
        ]);

        new UpdateKnowledgeList(
            user: $request->user(),
            list: $knowledgeList,
            title: $request->exists('title') ? $request->string('title')->toString() : null,
            description: $request->exists('description') ? $request->string('description')->toString() : null,
            folder: $this->folder($vault, $request->string('folder')->toString()),
        )->execute();

        return redirect()
            ->route('knowledge.lists.show', [$vault, $knowledgeList])
            ->with('status', __('The list is saved.'));
    }

    public function destroy(Request $request, Vault $vault, KnowledgeList $knowledgeList): RedirectResponse
    {
        new DestroyKnowledgeList(
            user: $request->user(),
            list: $knowledgeList,
        )->execute();

        return redirect()
            ->route('knowledge.index', $vault)
            ->with('status', __('The list is deleted.'));
    }

    private function folder(Vault $vault, string $uuid): ?KnowledgeFolder
    {
        if ($uuid === '') {
            return null;
        }

        return KnowledgeFolder::query()->where('vault_id', $vault->id)->where('uuid', $uuid)->firstOrFail();
    }
}
