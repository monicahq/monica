<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\GenerateSecurityKeyOptions;
use App\Helpers\PasskeyAvailability;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Support\WebAuthn;

class SecurityKeyOptionsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        abort_unless(PasskeyAvailability::onThisInstance(), 404);

        $options = new GenerateSecurityKeyOptions(
            user: $request->user(),
        )->execute();

        $request->session()->put('securityKey.registrationOptions', WebAuthn::toJson($options));

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }
}
