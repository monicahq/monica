<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Actions\AttemptLogin;
use App\Actions\CreateApiKey;
use App\Actions\DestroyApiKey;
use App\Actions\VerifyTwoFactorCode;
use App\Helpers\TextSanitizer;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Trade an email address and a password for a token, and give the token back.
 *
 * The rules are not written here. `AttemptLogin` holds them, the sign in screen
 * asks it the same thing, and it is asked without a session because a token is
 * not one. All that is left here is turning its refusal into the answer an API
 * client expects, which is a 401 rather than the field error a form wants.
 *
 * A second factor is checked before any token exists. Skipping it would make
 * this endpoint a way around the second factor rather than another way in.
 */
class LoginController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $user = new AttemptLogin(
                email: $validated['email'],
                password: $validated['password'],
                ip: $request->ip(),
                stateless: true,
            )->execute();
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->validator->errors()->first(),
            ], 401);
        }

        if (! $this->passesSecondFactor($user, $validated['code'] ?? null)) {
            return response()->json([
                'message' => __('That two factor code is not valid.'),
            ], 401);
        }

        $token = new CreateApiKey(
            user: $user,
            name: $this->keyName($validated['device_name'] ?? null),
        )->execute();

        return response()->json([
            'token' => $token,
            'expires_at' => null,
        ], 200);
    }

    public function destroy(Request $request): Response
    {
        new DestroyApiKey(
            user: $request->user(),
            apiKeyId: (int) $request->user()->currentAccessToken()->getKey(),
        )->execute();

        return response()->noContent(204);
    }

    /**
     * Somebody who has not turned the second factor on has nothing to prove.
     * Somebody who has must prove it here, with the code their authenticator is
     * showing or with one of their recovery codes.
     */
    private function passesSecondFactor(User $user, ?string $code): bool
    {
        if ($user->two_factor_confirmed_at === null) {
            return true;
        }

        if ($code === null || $code === '') {
            return false;
        }

        return new VerifyTwoFactorCode(user: $user, code: $code)->execute();
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
