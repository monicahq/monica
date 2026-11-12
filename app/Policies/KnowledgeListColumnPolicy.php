<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KnowledgeListColumn;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class KnowledgeListColumnPolicy
{
    public function delete(User $user, KnowledgeListColumn $column): Response
    {
        $otherColumns = KnowledgeListColumn::query()
            ->where('knowledge_list_id', $column->knowledge_list_id)
            ->whereKeyNot($column->id)
            ->exists();

        if (! $otherColumns) {
            return Response::deny(__('The last column cannot be removed. A list needs at least one.'));
        }

        return Response::allow();
    }
}
