<?php

namespace App\Settings\ManageUserPreferences\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageUserPreferences\Web\ViewHelpers\UserPreferencesIndexViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
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
