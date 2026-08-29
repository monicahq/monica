<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Http\Responses\LoginViewResponse as BaseLoginViewResponse;

/**
 * The challenge a passkey sign-in answers, always as JSON.
 *
 * The package's own response falls back to rendering a view when the request
 * does not ask for JSON, and the view it would render is configured to null
 * here, because the sign-in screen is this application's own. Answering JSON
 * unconditionally means a request that forgot its Accept header gets an
 * unusable challenge rather than an empty view.
 */
class PasskeyLoginViewResponse extends BaseLoginViewResponse
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
