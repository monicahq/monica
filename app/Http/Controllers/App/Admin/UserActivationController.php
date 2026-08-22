<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\ActivateUser;
use App\Actions\DeactivateUser;
use App\Exceptions\AdministratorCannotLockThemselvesOut;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserActivationController extends Controller
{
    public function create(Request $request, Account $account): RedirectResponse
    {
        new ActivateUser(
            user: $this->userOn($account),
            administrator: $request->user(),
        )->execute();

        return redirect()->route('admin.accounts.show', ['account' => $account->id])
            ->with('status', __('They can sign in again.'))
            ->with('status_description', __('Nothing they own was touched while they could not.'));
    }

    public function destroy(Request $request, Account $account): RedirectResponse
    {
        try {
            new DeactivateUser(
                user: $this->userOn($account),
                administrator: $request->user(),
            )->execute();
        } catch (AdministratorCannotLockThemselvesOut $exception) {
            return back()->withErrors(['activation' => $exception->getMessage()]);
        }

        return redirect()->route('admin.accounts.show', ['account' => $account->id])
            ->with('status', __('They can no longer sign in.'))
            ->with('status_description', __('Any session they had open has stopped working. Nothing they own has been deleted.'));
    }

    /**
     * An account whose person was deleted has nobody to activate or deactivate,
     * and there is no screen to reach this from either.
     */
    private function userOn(Account $account): User
    {
        $user = $account->user;

        if ($user === null) {
            throw new ModelNotFoundException('There is nobody on this account.');
        }

        return $user;
    }
}
