<?php

namespace App\Helpers;

use App\Models\User\User;
use App\Models\Journal\Day;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class JournalHelper
{
    /**
     * Get the number of paid accounts in the instance.
     *
     * @param User $user
     * @return bool
     */
    public static function hasAlreadyRatedToday(User $user): bool
    {
        try {
            Day::where('account_id', $user->account_id)
                ->where('date', now($user->timezone)->toDateString())
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return false;
        }

        return true;
    }
}
