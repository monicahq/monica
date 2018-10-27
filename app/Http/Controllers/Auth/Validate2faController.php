<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Validate2faController extends Controller
{
    /**
     * Redirect the user after 2fa form has been submitted.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('url')) {
            return redirect(urldecode($request->get('url')));
        }

        return redirect()->route('login');
    }

    public static function loginCallback()
    {
        app('pragmarx.google2fa')->login();
    }
}
