<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorChallengeViewResponse as TwoFactorChallengeViewContract;

class TwoFactorChallengeView implements TwoFactorChallengeViewContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $userId = $request->session()->get('login.id');
        $user = User::find($userId);

        return Inertia::render('Auth/TwoFactorChallenge', [
            'two_factor' => optional($user)->two_factor_secret && ! is_null(optional($user)->two_factor_confirmed_at),
            'remember' => $request->session()->get('login.remember'),
        ])->toResponse($request);
    }
}
