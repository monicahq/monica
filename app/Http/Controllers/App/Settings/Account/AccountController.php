<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account;

use App\Actions\DestroyAccount;
use App\Exceptions\ProviderUnreachable;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->merge(['confirm_email' => mb_strtolower(trim((string) $request->input('confirm_email')))]);

        $request->validate([
            'confirm_email' => ['required', 'string', Rule::in([$user->email])],
        ], [
            'confirm_email.in' => __('Type your email address exactly as it is shown above.'),
        ]);

        try {
            new DestroyAccount(user: $user, account: $user->account)->execute();
        } catch (ProviderUnreachable $e) {
            return back()->withErrors(['confirm_email' => $e->getMessage()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login.new')
            ->with('status', __('Your account is deleted. Everything in it is gone.'));
    }
}
