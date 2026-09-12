<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyConfirmationResponse as PasskeyConfirmationResponseContract;

class PasskeyConfirmationResponse implements PasskeyConfirmationResponseContract
{
    /** @param  Request  $request */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $target = redirect()->intended(route('settings.security.index'));

        if ($request->wantsJson()) {
            return new JsonResponse(['redirect' => $target->getTargetUrl()]);
        }

        return $target;
    }
}
