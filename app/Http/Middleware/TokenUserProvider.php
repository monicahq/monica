<?php

namespace App\Http\Middleware;

use App\Models\User\User;
use Illuminate\Auth\DatabaseUserProvider;

class TokenUserProvider extends DatabaseUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        $token = parent::retrieveByCredentials($credentials);

        $user = User::find($token->user_id);
        $user->token_id = $token->id;

        return $user;
    }
}
