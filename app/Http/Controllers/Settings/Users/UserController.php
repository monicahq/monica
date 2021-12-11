<?php

namespace App\Http\Controllers\Settings\Users;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Account\ManageUsers\InviteUser;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Settings\Users\ViewHelpers\UserIndexViewHelper;
use App\Http\Controllers\Settings\Users\ViewHelpers\UserCreateViewHelper;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Users/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => UserIndexViewHelper::data(Auth::user()),
        ]);
    }

    public function create()
    {
        return Inertia::render('Settings/Users/Create', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => UserCreateViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'email' => $request->input('email'),
        ];

        (new InviteUser)->execute($data);

        return response()->json([
            'data' => route('settings.user.index'),
        ], 201);
    }
}
