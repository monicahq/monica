<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\OAuth;

use App\Actions\RevokeOAuthConnection;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

/**
 * Where an app signs itself out with its refresh token (RFC 7009). It answers the same way
 * whether or not the token was known, so nobody learns anything by trying tokens. A malformed
 * request answers in the OAuth error shape rather than Laravel's, as the RFC asks.
 */
class RevocationController extends Controller
{
    public function create(Request $request): Response|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'max:4096'],
            'client_id' => ['required', 'string', 'max:255'],
            'token_type_hint' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => $validator->errors()->first(),
            ], 400);
        }

        $validated = $validator->validated();

        new RevokeOAuthConnection(
            clientId: $validated['client_id'],
            refreshToken: $validated['token'],
        )->execute();

        return response()->noContent(200);
    }
}
