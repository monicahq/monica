<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\CreateApiKey;
use App\Actions\DestroyApiKey;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    /**
     * The key itself rides back on the session rather than being written down,
     * so the screen can print it once and never again.
     */
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $apiKey = new CreateApiKey(
            user: $request->user(),
            name: $validated['name'],
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('apiKey', $apiKey)
            ->with('status', __('Your API key is ready.'))
            ->with('status_description', __('Copy it now. It is not shown again.'));
    }

    public function destroy(Request $request, int $apiKey): RedirectResponse
    {
        new DestroyApiKey(
            user: $request->user(),
            apiKeyId: $apiKey,
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('The API key is revoked.'))
            ->with('status_description', __('Anything still using it stops working.'));
    }
}
