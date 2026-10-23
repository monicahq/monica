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
                'message' => __('That two-factor code is not valid.'),
            ], 401);
        }

        $token = new CreateApiKey(
            user: $user,
            name: $this->keyName($validated['device_name'] ?? null),
            scopes: ['*'],
        )->execute()->plainTextToken;

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

    private function passesSecondFactor(User $user, ?string $code): bool
    {
        if (! $user->usesTwoFactorAuthentication()) {
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
