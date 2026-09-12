<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\ConfirmPassword;
use App\Http\Controllers\Controller;
use App\ViewModels\Auth\ConfirmPasswordViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmPasswordController extends Controller
{
    public function new(Request $request): View
    {
        return view('app.auth.confirm-password', [
            'viewModel' => new ConfirmPasswordViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255'],
        ]);

        $confirmed = new ConfirmPassword(
            user: $request->user(),
            password: $validated['password'],
        )->execute();

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'password' => __('That is not the right password.'),
            ]);
        }

        $request->session()->passwordConfirmed();

        $intended = redirect()->intended(route('settings.security.index'));

        if ($request->wantsJson()) {
            return response()->json(['redirect' => $intended->getTargetUrl()]);
        }

        return $intended;
    }
}
