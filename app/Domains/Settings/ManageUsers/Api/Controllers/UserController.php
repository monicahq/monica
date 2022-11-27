<?php

namespace App\Domains\Settings\ManageUsers\Api\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read')->only(['user', 'index', 'show']);

        parent::__construct();
    }

    /**
     * Retrieve the authenticated user
     *
     * Get the authenticated user.
     *
     * @group Account management
     * @subgroup Users
     * @apiResourceModel \App\Models\User
     * @response status=404 scenario="user not found" {"message": "User not found"}
     */
    public function user(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Retrieve a user
     *
     * Get a specific user object.
     *
     * @group Account management
     * @subgroup Users
     * @apiResourceModel \App\Models\User
     */
    public function show(Request $request, int $userId)
    {
        try {
            $user = User::where('account_id', Auth::user()->account_id)
                ->findOrFail($userId);
        } catch (ModelNotFoundException) {
            return $this->respondNotFound();
        }

        return new UserResource($user);
    }

    /**
     * List all users
     *
     * Get all the users in the account.
     *
     * @group Account management
     * @subgroup Users
     * @queryParam limit int A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10. Example: 10
     * @apiResourceModel \App\Models\User
     */
    public function index(Request $request)
    {
        try {
            $users = Auth::user()->account->users()
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return UserResource::collection($users);
    }
}
