<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\CreateSecurityKey;
use App\Actions\DestroySecurityKey;
use App\Actions\UpdateSecurityKey;
use App\Helpers\PasskeyAvailability;
use App\Http\Controllers\Controller;
use App\Models\SecurityKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SecurityKeyController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        abort_unless(PasskeyAvailability::onThisInstance(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
        ]);

        $options = $request->session()->pull('securityKey.registrationOptions');

        if (! is_string($options)) {
            return response()->json([
                'message' => __('That took too long. Please try again.'),
            ], 422);
        }

        $turnsTwoFactorOn = ! $request->user()->usesTwoFactorAuthentication();

        try {
            $securityKey = new CreateSecurityKey(
                user: $request->user(),
                name: $validated['name'],
                credential: $validated['credential'],
                options: $options,
            )->execute();
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        if (! $turnsTwoFactorOn) {
            $request->session()->flash('status', __('Your security key is added.'));

            return response()->json([
                'id' => $securityKey->id,
                'name' => $securityKey->name,
                'redirect' => route('settings.security.index'),
            ], 201);
        }

        $request->session()->flash('recoveryCodes', $request->user()->refresh()->two_factor_recovery_codes);
        $request->session()->flash('status', __('Two-factor authentication is on.'));
        $request->session()->flash('status_description', __('Your security key answers the second step when you sign in.'));

        return response()->json([
            'id' => $securityKey->id,
            'name' => $securityKey->name,
            'redirect' => route('settings.recoveryCodes.show'),
        ], 201);
    }

    public function update(Request $request, SecurityKey $securityKey): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        new UpdateSecurityKey(
            user: $request->user(),
            securityKey: $securityKey,
            name: $validated['name'],
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('Your security key is renamed.'));
    }

    public function destroy(Request $request, SecurityKey $securityKey): RedirectResponse
    {
        new DestroySecurityKey(
            user: $request->user(),
            securityKey: $securityKey,
        )->execute();

        if ($request->user()->usesTwoFactorAuthentication()) {
            return redirect()->route('settings.security.index')
                ->with('status', __('Your security key is removed.'));
        }

        return redirect()->route('settings.security.index')
            ->with('status', __('Two-factor authentication is off.'))
            ->with('status_description', __('Your password is now all it takes to sign in.'));
    }
}
