<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

/**
 * Where somebody lands once their passkey has signed them in.
 *
 * The package sends them to a fixed path read from configuration. This
 * application sends them where they were going, falling back to the home
 * screen, which is what the password sign-in does. The path cannot live in
 * configuration because it is a named route, and resolving a route inside a
 * config file breaks config caching.
 *
 * The browser side is a fetch call, so the answer is JSON carrying the address
 * rather than a redirect it would have to follow itself.
 */
class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $target = redirect()->intended(route('home.index'));

        if ($request->wantsJson()) {
            return new JsonResponse(['redirect' => $target->getTargetUrl()]);
        }

        return $target;
    }
}
