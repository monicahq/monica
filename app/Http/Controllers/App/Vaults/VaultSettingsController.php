<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultSettingsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VaultSettingsController extends Controller
{
    public function index(Request $request, Vault $vault): View
    {
        return view('app.vaults.settings', [
            'viewModel' => new VaultSettingsViewModel(
                user: $request->user(),
                vault: $vault,
            ),
        ]);
    }
}
