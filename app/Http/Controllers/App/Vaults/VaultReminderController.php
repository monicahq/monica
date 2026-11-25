<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultRemindersHistoryViewModel;
use App\ViewModels\Vaults\VaultRemindersSentViewModel;
use App\ViewModels\Vaults\VaultRemindersViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VaultReminderController extends Controller
{
    public function index(Request $request, Vault $vault): View
    {
        return view('app.vaults.reminders.index', [
            'viewModel' => new VaultRemindersViewModel(
                user: $request->user(),
                vault: $vault,
                groupBy: VaultRemindersViewModel::GROUP_BY_WHEN,
                page: $request->integer('page', 1),
            ),
        ]);
    }

    public function byContact(Request $request, Vault $vault): View
    {
        return view('app.vaults.reminders.index', [
            'viewModel' => new VaultRemindersViewModel(
                user: $request->user(),
                vault: $vault,
                groupBy: VaultRemindersViewModel::GROUP_BY_CONTACT,
                page: $request->integer('page', 1),
            ),
        ]);
    }

    public function sent(Request $request, Vault $vault): View
    {
        return view('app.vaults.reminders.sent', [
            'viewModel' => new VaultRemindersSentViewModel(
                user: $request->user(),
                vault: $vault,
            ),
        ]);
    }

    public function history(Request $request, Vault $vault): View
    {
        return view('app.vaults.reminders.history', [
            'viewModel' => new VaultRemindersHistoryViewModel(
                user: $request->user(),
                vault: $vault,
            ),
        ]);
    }
}
