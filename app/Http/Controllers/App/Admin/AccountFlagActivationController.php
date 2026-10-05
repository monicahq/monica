<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\ActivateFlag;
use App\Actions\DeactivateFlag;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountFlagActivationController extends Controller
{
    public function create(Request $request, Account $account, string $flag): RedirectResponse
    {
        new ActivateFlag(
            account: $account,
            flag: $flag,
            administrator: $request->user(),
        )->execute();

        return redirect()->route('admin.accounts.show', ['account' => $account->id])
            ->with('status', __('The flag is on for this account.'));
    }

    public function destroy(Request $request, Account $account, string $flag): RedirectResponse
    {
        new DeactivateFlag(
            account: $account,
            flag: $flag,
            administrator: $request->user(),
        )->execute();

        return redirect()->route('admin.accounts.show', ['account' => $account->id])
            ->with('status', __('The flag is off for this account.'))
            ->with('status_description', __('Nothing they made with it has been deleted.'));
    }
}
