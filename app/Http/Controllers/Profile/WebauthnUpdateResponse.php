<?php

namespace App\Http\Controllers\Profile;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\UpdateResponse as UpdateResponseContract;
use LaravelWebauthn\Facades\Webauthn;

class WebauthnUpdateResponse implements UpdateResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? Response::noContent()
            : Redirect::intended(Webauthn::redirects('register'));
    }
}
