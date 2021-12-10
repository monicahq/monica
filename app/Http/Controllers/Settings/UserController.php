<?php

namespace App\Http\Controllers\Settings;

use Inertia\Inertia;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Vault\ManageUsers\UserIndexViewHelper;
use App\Services\Vault\ManageVault\VaultIndexViewHelper;

class UserController extends Controller
{
    /**
     * Show the users page.
     *
     * @return Response
     */
    public function index()
    {
        return Inertia::render('Settings/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => UserIndexViewHelper::data(Auth::user()->account),
        ]);
    }
}
