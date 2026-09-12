<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\RegenerateTwoFactorRecoveryCodes;
use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Security\RecoveryCodesViewModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RecoveryCodeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->get('recoveryCodes');

        if ($codes === null) {
            return redirect()->route('settings.security.index');
        }

        return view('app.settings.account.security.recovery-codes', [
            'viewModel' => new RecoveryCodesViewModel(
                user: $request->user(),
                codes: $codes,
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $user = new RegenerateTwoFactorRecoveryCodes(
            user: $request->user(),
        )->execute();

        return redirect()->route('settings.recoveryCodes.show')
            ->with('recoveryCodes', $user->two_factor_recovery_codes)
            ->with('status', __('Your recovery codes are new.'))
            ->with('status_description', __('The ones you had before no longer work.'));
    }
}
