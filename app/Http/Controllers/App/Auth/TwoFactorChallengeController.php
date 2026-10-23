<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\VerifySecondFactorKey;
use App\Actions\VerifyTwoFactorCode;
use App\Enums\SignInMethodEnum;
use App\Enums\SsoProviderEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use App\ViewModels\Auth\TwoFactorViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function new(Request $request): View|RedirectResponse
    {
        $user = User::query()->find($request->session()->get('twoFactor.user.id'));

        if ($user === null) {
            return redirect()->route('auth.login.new');
        }

        return view('app.auth.two-factor-challenge', [
            'viewModel' => new TwoFactorViewModel(
                user: $user,
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->has('credential')) {
            return $this->createWithKey($request);
        }

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

        $this->signIn($request, $user);

        return redirect()->intended(route('home.index'));
    }

    private function createWithKey(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
        ]);

        $user = User::query()->find($request->session()->get('twoFactor.user.id'));
        $options = $request->session()->pull('twoFactor.keyOptions');

        if ($user === null || ! is_string($options)) {
            return response()->json([
                'message' => __('That took too long. Please sign in again.'),
                'redirect' => route('auth.login.new'),
            ], 422);
        }

        try {
            new VerifySecondFactorKey(
                user: $user,
                credential: $validated['credential'],
                options: $options,
            )->execute();
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        $this->signIn($request, $user);

        return response()->json([
            'redirect' => redirect()->intended(route('home.index'))->getTargetUrl(),
        ]);
    }

    private function signIn(Request $request, User $user): void
    {
        Auth::guard('web')->login($user, remember: (bool) $request->session()->pull('twoFactor.remember', false));

        $request->session()->forget(['twoFactor.user.id', 'twoFactor.keyOptions']);
        $request->session()->regenerate();

        $method = SignInMethodEnum::tryFrom((string) $request->session()->pull('twoFactor.signInMethod'));
        $provider = SsoProviderEnum::tryFrom((string) $request->session()->pull('twoFactor.provider'));

        if ($method !== null) {
            $user->notify(new NewLoginDetected(
                ip: $request->ip() ?? '',
                method: $method,
                provider: $provider,
            ));
        }
    }
}
