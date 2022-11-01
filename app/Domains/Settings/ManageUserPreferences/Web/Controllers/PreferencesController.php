<?php

namespace App\Domains\Settings\ManageUserPreferences\Web\Controllers;

use App\Domains\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PreferencesController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Preferences/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => UserPreferencesIndexViewHelper::data(Auth::user()),
        ]);
    }
}
