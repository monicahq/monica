<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\VerifyTwoFactorCode;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\ViewModels\Auth\TwoFactorViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function new(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('twoFactor.user.id')) {
            return redirect()->route('auth.login.new');
        }

        return view('app.auth.two-factor-challenge', [
            'viewModel' => new TwoFactorViewModel,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()->find($request->session()->get('twoFactor.user.id'));

        if ($user === null) {
            return redirect()->route('auth.login.new')
                ->withErrors(['code' => __('That took too long. Please sign in again.')]);
        }

        $verified = new VerifyTwoFactorCode(
            user: $user,
            code: $validated['code'],
        )->execute();

        if (! $verified) {
            return back()->withErrors(['code' => __('That code is not right.')]);
        }

        Auth::guard('web')->login($user);

        $request->session()->forget('twoFactor.user.id');
        $request->session()->regenerate();

        return redirect()->intended(route('home.index'));
    }
}
