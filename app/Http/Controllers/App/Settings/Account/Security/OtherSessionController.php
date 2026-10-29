<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\DestroyOtherSessions;
use App\Exceptions\PasswordIsWrong;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OtherSessionController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        try {
            new DestroyOtherSessions(
                user: $request->user(),
                password: $request->string('password')->toString(),
                currentSessionId: $request->session()->getId(),
            )->execute();
        } catch (PasswordIsWrong) {
            return redirect()->route('settings.security.index')
                ->with('error', __('That is not your password. No device was signed out.'));
        }

        return redirect()->route('settings.security.index')
            ->with('status', __('Every other device is signed out.'))
            ->with('status_description', __('Each one will be asked to sign in at its next request.'));
    }
}
