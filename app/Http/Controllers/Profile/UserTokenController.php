<?php

namespace App\Http\Controllers\Profile;

use Illuminate\Http\Request;
use Laravel\Jetstream\Http\Controllers\Inertia\UserProfileController as BaseController;

class UserTokenController extends BaseController
{
    /**
     * Destroy user token.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, string $driver)
    {
        $request->user()->userTokens()
            ->where('driver', $driver)
            ->delete();

        return redirect()->route('profile.show');
    }
}
