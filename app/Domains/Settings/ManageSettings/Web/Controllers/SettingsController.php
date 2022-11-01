<?php

namespace App\Domains\Settings\ManageSettings\Web\Controllers;

use App\Domains\Settings\ManageSettings\Web\ViewHelpers\SettingsIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => SettingsIndexViewHelper::data(Auth::user()),
        ]);
    }
}
