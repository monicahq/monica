<?php

namespace App\Settings\ManageSettings\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManageSettings\Web\ViewHelpers\SettingsIndexViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
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
