<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\ActivateFlagForEveryAccount;
use App\Actions\DeactivateFlagForEveryAccount;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FlagActivationController extends Controller
{
    public function create(Request $request, string $flag): RedirectResponse
    {
        new ActivateFlagForEveryAccount(
            flag: $flag,
            administrator: $request->user(),
        )->execute();

        return redirect()->route('admin.flags.index')
            ->with('status', __('The flag is on for every account.'))
            ->with('status_description', __('Accounts created from now on have it too.'));
    }

    public function destroy(Request $request, string $flag): RedirectResponse
    {
        new DeactivateFlagForEveryAccount(
            flag: $flag,
            administrator: $request->user(),
        )->execute();

        return redirect()->route('admin.flags.index')
            ->with('status', __('The flag is off for every account.'))
            ->with('status_description', __('Nothing anybody made with it has been deleted.'));
    }
}
