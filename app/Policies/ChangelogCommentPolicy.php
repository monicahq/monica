<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChangelogComment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChangelogCommentPolicy
{
    public function hide(User $user, ChangelogComment $changelogComment): Response
    {
        if ($changelogComment->is_private) {
            return Response::deny(__('A private comment is shown to nobody, so there is nothing to hide.'));
        }

        return Response::allow();
    }
}
