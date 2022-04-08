<?php

namespace App\Settings\ManageUsers\Web\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Settings\ManageUsers\Services\InviteUser;
use App\Settings\ManageUsers\Services\DestroyUser;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Settings\ManageUsers\Services\GiveAdministratorPrivilege;
use App\Settings\ManageUsers\Web\ViewHelpers\UserIndexViewHelper;
use App\Settings\ManageUsers\Web\ViewHelpers\UserCreateViewHelper;
use App\Settings\ManageUsers\Services\RemoveAdministratorPrivilege;

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
            'is_administrator' => $request->input('administrator') === 'true' ? true : false,
        ];

        (new InviteUser)->execute($data);

        return response()->json([
            'data' => route('settings.user.index'),
        ], 201);
    }

    public function update(Request $request, int $userId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'user_id' => $userId,
        ];

        if ($request->input('administrator') === 'true') {
            $user = (new GiveAdministratorPrivilege)->execute($data);
        } else {
            $user = (new RemoveAdministratorPrivilege)->execute($data);
        }

        return response()->json([
            'data' => UserIndexViewHelper::dtoUser($user, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, int $userId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'user_id' => $userId,
        ];

        (new DestroyUser)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
