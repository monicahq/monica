<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\GenerateSecondFactorOptions;
use App\Helpers\PasskeyAvailability;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Support\WebAuthn;

class TwoFactorKeyOptionsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        abort_unless(PasskeyAvailability::onThisInstance(), 404);

        $user = User::query()->find($request->session()->get('twoFactor.user.id'));

        if ($user === null) {
            return response()->json([
                'message' => __('That took too long. Please sign in again.'),
                'redirect' => route('auth.login.new'),
            ], 422);
        }

        $options = new GenerateSecondFactorOptions(
            user: $user,
        )->execute();

        $request->session()->put('twoFactor.keyOptions', WebAuthn::toJson($options));

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }
}
