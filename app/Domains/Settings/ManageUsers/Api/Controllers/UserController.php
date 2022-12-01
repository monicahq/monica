<?php

namespace App\Domains\Settings\ManageUsers\Api\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

/**
 * @group Account management
 * @subgroup Users
 */
class UserController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read');

        parent::__construct();
    }

    /**
     * Retrieve the authenticated user
     *
     * Get the authenticated user.
     */
    #[ResponseFromApiResource(UserResource::class, User::class)]
    #[Response(['message' => 'User not found'], status: 404, description: 'User not found')]
    public function user(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Retrieve a user
     *
     * Get a specific user object.
     */
    #[ResponseFromApiResource(UserResource::class, User::class)]
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
     */
    #[QueryParam('limit', 'int', description: 'A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.', required: false, example: 10)]
    #[ResponseFromApiResource(UserResource::class, User::class, collection: true)]
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
