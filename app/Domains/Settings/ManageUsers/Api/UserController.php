<?php

namespace App\Domains\Settings\ManageUsers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * GET api/user
     *
     * Get the authenticated User.
     *
     * @apiResourceModel \App\Models\User
     */
    public function __invoke(Request $request)
    {
        return $request->user();
    }
}
