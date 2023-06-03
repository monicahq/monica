<?php

namespace App\Domains\Settings\ManageUsers\Api\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\{QueryParam,Response,ResponseFromApiResource};

/**
 * @group Account management
 *
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
     * Retrieve the authenticated user.
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
     * Retrieve a user.
     *
     * Get a specific user object.
     */
    #[ResponseFromApiResource(UserResource::class, User::class)]
    public function show(Request $request, string $userId)
    {
        $user = $request->user()->account->users()
            ->findOrFail($userId);

        return new UserResource($user);
    }

    /**
     * List all users.
     *
     * Get all the users in the account.
     */
    #[QueryParam('limit', 'int', description: 'A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.', required: false, example: 10)]
    #[ResponseFromApiResource(UserResource::class, User::class, collection: true)]
    public function index(Request $request)
    {
        $users = $request->user()->account->users()
            ->paginate($this->getLimitPerPage());

        return UserResource::collection($users);
    }
}
