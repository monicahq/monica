<?php

namespace App\Http\Controllers;

use App\Models\User\User;
use App\Jobs\SendMailTest;
use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\Account\Account;
use App\Http\Resources\Account\User\User as UserResource;
use App\Http\Resources\Account\User\Account as AccountResource;
use App\Http\Resources\Account\User\UserShort as UserShortResource;

class AdminController extends Controller
{
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('checkadmin');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        return view('admin.index')
            ->withUsers(UserShortResource::collection(User::all()));
    }

    /**
     * Get list of users.
     *
     * @param Request $request
     * @return array
     */
    public function users(Request $request)
    {
        $perPage = $request->has('perPage') ? $request->get('perPage') : config('monica.number_of_contacts_pagination');

        $users = User::where('id', '>', '0');

        $queryString = StringHelper::buildQuery([
            'first_name',
            'last_name',
            'email',
        ], $request->get('search') ? $request->get('search') : '');

        $users->whereRaw('('.$queryString.')');
        $users->orderByRaw('created_at asc');
        $users = $users->paginate($perPage);

        return [
            'total' => $users->total(),
            'entries' => UserResource::collection($users),
        ];
    }

    /**
     * Get one user.
     *
     * @param Request $request
     * @return array
     */
    public function user(Request $request, User $user)
    {
        return [
            'entry' => new UserResource($user),
        ];
    }

    /**
     * Switch admin user.
     *
     * @param  Request $request
     * @param  User $user
     * @return array
     */
    public function userAdminToggle(Request $request, User $user)
    {
        $user->admin = ! $user->admin;
        $user->save();

        return [
            'entry' => new UserResource($user),
        ];
    }

    /**
     * Switch free access account.
     *
     * @param  Request $request
     * @param  Account $account
     * @return array
     */
    public function accountPremiumToggle(Request $request, Account $account)
    {
        $account->has_access_to_paid_version_for_free = ! $account->has_access_to_paid_version_for_free;
        $account->save();

        return [
            'entry' => new AccountResource($account),
        ];
    }

    /**
     * Test email sending.
     *
     * @param  Request $request
     */
    public function email(Request $request)
    {
        dispatch(new SendMailTest($request->input('email')));

        return ['true'];
    }
}
