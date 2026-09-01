<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Actions\CreateAccount;
use App\Actions\CreateApiKey;
use App\Helpers\TextSanitizer;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Open an account from outside the browser.
 *
 * The account this builds is the account the sign up screen builds, down to the
 * first vault and the lists it starts with, because it asks the same action for
 * it. The terms are asked for here too: the screen refuses to create an account
 * without them, so a second way in that skipped them would be a way around
 * them rather than a convenience.
 *
 * The bot challenge the screen carries is a browser thing and has no meaning
 * over the API. What stands in its place is the rate limit on this route.
 */
class RegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class, 'disposable_email'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(8)],
            'timezone' => ['nullable', 'string', 'timezone:all'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => __('You have to agree with the terms of use and the privacy policy to create an account.'),
        ]);

        $user = new CreateAccount(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            email: $validated['email'],
            password: $validated['password'],
            timezone: $validated['timezone'] ?? null,
        )->execute();

        $token = new CreateApiKey(
            user: $user,
            name: $this->keyName($validated['device_name'] ?? null),
        )->execute()->plainTextToken;

        return response()->json([
            'token' => $token,
            'expires_at' => null,
        ], 201);
    }

    /**
     * Name the key after whatever asked for it, so the list on the security
     * screen says which integration each one belongs to. A key nobody named is
     * still identifiable by the date it was made, so an unnamed one is allowed.
     */
    private function keyName(?string $deviceName): string
    {
        $deviceName = TextSanitizer::plainText((string) $deviceName);

        if ($deviceName === '') {
            return __('Unnamed integration');
        }

        return $deviceName;
    }
}
