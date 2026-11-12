<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\UpdateKnowledgeListColumnPosition;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KnowledgeListColumnPositionController extends Controller
{
    public function update(Request $request, Vault $vault, KnowledgeList $knowledgeList, KnowledgeListColumn $knowledgeListColumn): RedirectResponse
    {
        abort_unless($knowledgeListColumn->knowledge_list_id === $knowledgeList->id, 404);

        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateKnowledgeListColumnPosition(
            user: $request->user(),
            column: $knowledgeListColumn,
            position: $request->integer('position'),
        )->execute();

        return redirect()
            ->route('knowledge.listColumns.index', [$vault, $knowledgeList])
            ->with('status', __('The column is moved.'));
    }
}
