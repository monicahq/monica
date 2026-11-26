<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\ConfirmEmailAddress;
use App\Actions\SendEmailVerification;
use App\Http\Controllers\Controller;
use App\ViewModels\Auth\VerifyEmailViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home.index');
        }

        return view('app.auth.verify-email', [
            'viewModel' => new VerifyEmailViewModel(
                email: $request->user()->email,
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! hash_equals((string) $user->getKey(), (string) $request->route()->parameter('id'))) {
            abort(403);
        }

        if (! hash_equals(hash('sha256', $user->getEmailForVerification()), (string) $request->route()->parameter('hash'))) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('home.index');
        }

        new ConfirmEmailAddress(user: $user)->execute();

        return redirect()->route('home.index')->with('status', __('Your email address is confirmed.'));
    }

    public function create(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home.index');
        }

        new SendEmailVerification(user: $request->user())->execute();

        return back()->with('status', __('We sent you another email.'));
    }
}
