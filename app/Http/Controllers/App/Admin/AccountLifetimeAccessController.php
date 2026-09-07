<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\GrantLifetimeAccess;
use App\Actions\WithdrawLifetimeAccess;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountLifetimeAccessController extends Controller
{
    public function create(Request $request, Account $account): RedirectResponse
    {
        new GrantLifetimeAccess(
            account: $account,
            administrator: $request->user(),
        )->execute();

        return redirect()->route('admin.accounts.show', ['account' => $account->id])
            ->with('status', __('The account has lifetime access.'))
            ->with('status_description', __('It takes effect immediately, and the grant is recorded against your name.'));
    }

    public function destroy(Request $request, Account $account): RedirectResponse
    {
        new WithdrawLifetimeAccess(
            account: $account,
            administrator: $request->user(),
        )->execute();

        return redirect()->route('admin.accounts.show', ['account' => $account->id])
            ->with('status', __('Lifetime access is withdrawn.'))
            ->with('status_description', __('The purchase, if there was one, is still on the record.'));
    }
}
