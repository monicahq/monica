<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Helpers\ShareLinkPassword;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The password asked for before a shared page opens.
 *
 * It does not check the password. It hands it to the browsing session and sends
 * the visitor at the page, which is the one thing that checks: the page opens the
 * link on every view anyway, so checking here as well would write two visits for
 * one opening and tell the owner a story about traffic that did not happen.
 *
 * A password that does not open the link comes back here as the same form with its
 * refusal, because the page forgets what it was given and says which of the two
 * states it is in. A wrong attempt is written down by the action, as it must be: a
 * run of them is the only signal the owner ever gets that a link may have leaked.
 */
class SharedContactPasswordController extends Controller
{
    public function create(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255'],
        ]);

        ShareLinkPassword::remember($request, $token, $validated['password']);

        return redirect()->route('share.show', ['token' => $token]);
    }
}
