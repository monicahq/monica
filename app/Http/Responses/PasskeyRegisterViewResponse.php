<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Http\Responses\RegisterViewResponse as BaseRegisterViewResponse;

/**
 * The challenge a new passkey is created against, always as JSON. The reasoning
 * is the same as PasskeyLoginViewResponse: the screen belongs to this
 * application, so the package only ever needs to hand back a challenge.
 */
class PasskeyRegisterViewResponse extends BaseRegisterViewResponse
{
    /**
     * @param  Request  $request
     */
    #[\Override]
    public function toResponse($request): JsonResponse
    {
        return Response::json(['publicKey' => $this->publicKey]);
    }
}
