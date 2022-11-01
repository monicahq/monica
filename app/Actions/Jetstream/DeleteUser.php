<?php

namespace App\Actions\Jetstream;

use App\Domains\Settings\ManageUsers\Services\DestroyUser;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     *
     * @param  mixed  $user
     * @return void
     */
    public function delete($user)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'user_id' => $user->id,
        ];

        app(DestroyUser::class)->execute($data);
    }
}
