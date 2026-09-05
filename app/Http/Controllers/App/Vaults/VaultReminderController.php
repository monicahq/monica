<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Vault;
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
                groupBy: VaultRemindersViewModel::GroupByWhen,
            ),
        ]);
    }

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
