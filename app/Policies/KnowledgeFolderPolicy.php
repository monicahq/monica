<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KnowledgeFolder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class KnowledgeFolderPolicy
{
    public function delete(User $user, KnowledgeFolder $folder): Response
    {
        $otherFolders = KnowledgeFolder::query()
            ->where('vault_id', $folder->vault_id)
            ->whereKeyNot($folder->id)
            ->exists();

        if (! $otherFolders) {
            return Response::deny(__('The last folder cannot be deleted. Knowledge needs at least one.'));
        }

        return Response::allow();
    }
}
