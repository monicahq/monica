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
 * The grouping arrives in the query string rather than in the path. It is a way
 * of reading the same list, not a different list, and a route segment for it
 * would put a presentation choice into the address of the resource. Anything
 * other than the two groupings falls back to the default rather than being
 * refused, because a mistyped address should still draw the screen.
 */
class VaultReminderController extends Controller
{
    public function index(Request $request, Vault $vault): View
    {
        return view('app.vaults.reminders.index', [
            'viewModel' => new VaultRemindersViewModel(
                user: $request->user(),
                vault: $vault,
                groupBy: $request->string('group')->toString(),
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
