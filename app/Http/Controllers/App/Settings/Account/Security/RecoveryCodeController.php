<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\RegenerateTwoFactorRecoveryCodes;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RecoveryCodeController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        new RegenerateTwoFactorRecoveryCodes(
            user: $request->user(),
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('Your recovery codes are new.'))
            ->with('status_description', __('The ones you had before no longer work.'));
    }
}
