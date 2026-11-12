<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateKnowledgeListItem;
use App\Actions\DestroyKnowledgeListItem;
use App\Actions\UpdateKnowledgeListItem;
use App\Helpers\KnowledgeListCells;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListItem;
use App\Models\Vault;
use App\ViewModels\Vaults\KnowledgeListViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeListItemController extends Controller
{
    public function create(Request $request, Vault $vault, KnowledgeList $knowledgeList): RedirectResponse
    {
        new CreateKnowledgeListItem(
            user: $request->user(),
            list: $knowledgeList,
            cells: $this->cells($request),
        )->execute();

        return redirect()
            ->route('knowledge.lists.show', [$vault, $knowledgeList])
            ->with('status', __('The item is added.'));
    }

    public function edit(Request $request, Vault $vault, KnowledgeList $knowledgeList, KnowledgeListItem $knowledgeListItem): View
    {
        abort_unless($knowledgeListItem->knowledge_list_id === $knowledgeList->id, 404);

        return view('app.vaults.knowledge.list', [
            'viewModel' => new KnowledgeListViewModel(
                user: $request->user(),
                list: $knowledgeList,
                editingItem: $knowledgeListItem,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, KnowledgeList $knowledgeList, KnowledgeListItem $knowledgeListItem): RedirectResponse
    {
        abort_unless($knowledgeListItem->knowledge_list_id === $knowledgeList->id, 404);

        new UpdateKnowledgeListItem(
            user: $request->user(),
            item: $knowledgeListItem,
            cells: $this->cells($request),
        )->execute();

        return redirect()
            ->route('knowledge.lists.show', [$vault, $knowledgeList])
            ->with('status', __('The item is saved.'));
    }

    public function destroy(Request $request, Vault $vault, KnowledgeList $knowledgeList, KnowledgeListItem $knowledgeListItem): RedirectResponse
    {
        abort_unless($knowledgeListItem->knowledge_list_id === $knowledgeList->id, 404);

        new DestroyKnowledgeListItem(
            user: $request->user(),
            item: $knowledgeListItem,
        )->execute();

        return redirect()
            ->route('knowledge.lists.show', [$vault, $knowledgeList])
            ->with('status', __('The item is deleted.'));
    }

    /** @return array<string, string|null> */
    private function cells(Request $request): array
    {
        $request->validate([
            'cells' => ['required', 'array'],
            'cells.*' => ['nullable', 'string', 'max:'.KnowledgeListCells::MAX_LENGTH],
        ]);

        return collect($request->array('cells'))
            ->mapWithKeys(fn (mixed $value, int|string $uuid): array => [(string) $uuid => is_string($value) ? $value : null])
            ->all();
    }
}
