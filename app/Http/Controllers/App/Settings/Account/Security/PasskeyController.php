<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\UpdatePasskey;
use App\Http\Controllers\Controller;
use App\Models\Passkey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Renaming a passkey.
 *
 * Adding one, removing one and signing in with one all go through the package,
 * which fires events this application listens to. It offers no route for a
 * rename, so that one operation stays here.
 */
class PasskeyController extends Controller
{
    public function update(Request $request, Passkey $passkey): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        new UpdatePasskey(
            user: $request->user(),
            passkey: $passkey,
            name: $validated['name'],
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('Your passkey is renamed.'));
    }
}
