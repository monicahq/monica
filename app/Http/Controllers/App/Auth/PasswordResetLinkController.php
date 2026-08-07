<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Http\Controllers\Controller;
use App\ViewModels\Auth\PasswordResetViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function new(): View
    {
        return view('app.auth.forgot-password', [
            'viewModel' => new PasswordResetViewModel,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        // The answer is the same whether the address is known or not, so this
        // form cannot be used to find out who has an account here.
        Password::sendResetLink(['email' => mb_strtolower($validated['email'])]);

        return back()->with('status', __('If that address has an account, a link to choose a new password is on its way.'));
    }
}
