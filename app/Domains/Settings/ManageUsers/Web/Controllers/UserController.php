<?php

namespace App\Domains\Settings\ManageUsers\Web\Controllers;

use App\Domains\Settings\ManageUsers\Services\DestroyUser;
use App\Domains\Settings\ManageUsers\Services\GiveAdministratorPrivilege;
use App\Domains\Settings\ManageUsers\Services\InviteUser;
use App\Domains\Settings\ManageUsers\Services\RemoveAdministratorPrivilege;
use App\Domains\Settings\ManageUsers\Web\ViewHelpers\UserCreateViewHelper;
use App\Domains\Settings\ManageUsers\Web\ViewHelpers\UserIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
            'data' => UserCreateViewHelper::data(),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'email' => $request->input('email'),
            'is_administrator' => $request->input('administrator') === 'true' ? true : false,
        ];

        (new InviteUser)->execute($data);

        return response()->json([
            'data' => route('settings.user.index'),
        ], 201);
    }

    public function update(Request $request, string $userId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
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

    public function destroy(Request $request, string $userId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'user_id' => $userId,
        ];

        (new DestroyUser)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
