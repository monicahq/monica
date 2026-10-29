<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\DestroySession;
use App\Exceptions\CannotDeleteTheCurrentSession;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function destroy(Request $request, string $session): RedirectResponse
    {
        try {
            new DestroySession(
                user: $request->user(),
                sessionId: $session,
                currentSessionId: $request->session()->getId(),
            )->execute();
        } catch (CannotDeleteTheCurrentSession) {
            return redirect()->route('settings.security.index')
                ->with('error', __('This is the device you are on. Sign out to end it.'));
        }

        return redirect()->route('settings.security.index')
            ->with('status', __('The device is signed out.'))
            ->with('status_description', __('It will be asked to sign in at its next request.'));
    }
}
