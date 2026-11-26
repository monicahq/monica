<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\EndMobileSession;
use App\Http\Controllers\Controller;
use App\Models\MobileSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MobileSessionController extends Controller
{
    public function destroy(Request $request, MobileSession $mobileSession): RedirectResponse
    {
        abort_unless($mobileSession->ended_at === null, 404);

        new EndMobileSession(
            user: $request->user(),
            session: $mobileSession,
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('The phone is signed out.'))
            ->with('status_description', __('The app has to log in again before it can reach your account.'));
    }
}
