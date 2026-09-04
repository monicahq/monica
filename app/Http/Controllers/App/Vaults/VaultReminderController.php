<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultRemindersSentViewModel;
use App\ViewModels\Vaults\VaultRemindersViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Everything one vault will remind somebody about.
 *
 * A vault of another account never reaches here: `resolveRouteBinding()` on the
 * vault refuses it, so there is nothing for this controller to check beyond what
 * the binding already did.
 *
 * Each way of reading the list has an address of its own, one method each, the
 * way the tab beside it does. A grouping somebody chose is then something they
 * can link to and come back to, which a query string swapped in place never was.
 */
class VaultReminderController extends Controller
{
    public function index(Request $request, Vault $vault): View
    {
        return view('app.vaults.reminders.index', [
            'viewModel' => new VaultRemindersViewModel(
                user: $request->user(),
                vault: $vault,
                groupBy: VaultRemindersViewModel::GroupByWhen,
            ),
        ]);
    }

    /**
     * The same list, gathered under the person each reminder is about rather
     * than under when it fires.
     */
    public function byContact(Request $request, Vault $vault): View
    {
        return view('app.vaults.reminders.index', [
            'viewModel' => new VaultRemindersViewModel(
                user: $request->user(),
                vault: $vault,
                groupBy: VaultRemindersViewModel::GroupByContact,
            ),
        ]);
    }

    /**
     * What the vault has already sent. It is a tab rather than a filter of the
     * list beside it: the two answer different questions and neither is a
     * narrowing of the other, so each has an address somebody can link to.
     */
    public function sent(Request $request, Vault $vault): View
    {
        return view('app.vaults.reminders.sent', [
            'viewModel' => new VaultRemindersSentViewModel(
                user: $request->user(),
                vault: $vault,
            ),
        ]);
    }
}
