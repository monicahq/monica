<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\ConfirmTwoFactorAuthentication;
use App\Actions\DisableTwoFactorAuthentication;
use App\Actions\EnableTwoFactorAuthentication;
use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Security\TwoFactorEnrolmentViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function new(Request $request): View
    {
        $enrolment = new EnableTwoFactorAuthentication(
            user: $request->user(),
        )->execute();

        return view('app.settings.account.security.two-factor', [
            'viewModel' => new TwoFactorEnrolmentViewModel(
                user: $request->user(),
                employee: $request->user()->employee,
                secret: $enrolment['secret'],
                qrCode: $enrolment['qrCode'],
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $confirmed = new ConfirmTwoFactorAuthentication(
            user: $request->user(),
            code: $validated['code'],
        )->execute();

        if (! $confirmed) {
            return back()->withErrors(['code' => __('That code is not right. Check your authenticator app is showing the current one.')]);
        }

        return redirect()->route('settings.security.index')
            ->with('status', __('Two factor authentication is on.'))
            ->with('status_description', __('We will ask for a code the next time you sign in.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        new DisableTwoFactorAuthentication(
            user: $request->user(),
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('Two factor authentication is off.'))
            ->with('status_description', __('Your password is now all it takes to sign in.'));
    }
}
