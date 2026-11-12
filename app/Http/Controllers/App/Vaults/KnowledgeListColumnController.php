<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateKnowledgeListColumn;
use App\Actions\DestroyKnowledgeListColumn;
use App\Actions\UpdateKnowledgeListColumn;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\Vault;
use App\ViewModels\Vaults\KnowledgeListColumnsViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeListColumnController extends Controller
{
    public function index(Request $request, Vault $vault, KnowledgeList $knowledgeList): View
    {
        return view('app.vaults.knowledge.list-columns', [
            'viewModel' => new KnowledgeListColumnsViewModel(
                user: $request->user(),
                list: $knowledgeList,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, KnowledgeList $knowledgeList): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        new CreateKnowledgeListColumn(
            user: $request->user(),
            list: $knowledgeList,
            name: $request->string('name')->toString(),
        )->execute();

        return redirect()
            ->route('knowledge.listColumns.index', [$vault, $knowledgeList])
            ->with('status', __('The column is added.'));
    }

    public function update(Request $request, Vault $vault, KnowledgeList $knowledgeList, KnowledgeListColumn $knowledgeListColumn): RedirectResponse
    {
        abort_unless($knowledgeListColumn->knowledge_list_id === $knowledgeList->id, 404);

        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        new UpdateKnowledgeListColumn(
            user: $request->user(),
            column: $knowledgeListColumn,
            name: $request->string('name')->toString(),
        )->execute();

        return redirect()
            ->route('knowledge.listColumns.index', [$vault, $knowledgeList])
            ->with('status', __('The column is renamed.'));
    }

    public function destroy(Request $request, Vault $vault, KnowledgeList $knowledgeList, KnowledgeListColumn $knowledgeListColumn): RedirectResponse
    {
        abort_unless($knowledgeListColumn->knowledge_list_id === $knowledgeList->id, 404);

        try {
            new DestroyKnowledgeListColumn(
                user: $request->user(),
                column: $knowledgeListColumn,
            )->execute();
        } catch (AuthorizationException $e) {
            return redirect()->back()->withErrors(['column' => $e->getMessage()]);
        }

        return redirect()
            ->route('knowledge.listColumns.index', [$vault, $knowledgeList])
            ->with('status', __('The column is removed.'));
    }
}
