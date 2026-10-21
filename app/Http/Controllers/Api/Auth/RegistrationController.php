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

class RegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class, 'disposable_email'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::default()],
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
            scopes: ['*'],
        )->execute()->plainTextToken;

        return response()->json([
            'token' => $token,
            'expires_at' => null,
        ], 201);
    }

    private function keyName(?string $deviceName): string
    {
        $deviceName = TextSanitizer::plainText((string) $deviceName);

        if ($deviceName === '') {
            return __('Unnamed integration');
        }

        return $deviceName;
    }
}
