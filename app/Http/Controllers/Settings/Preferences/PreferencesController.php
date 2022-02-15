<?php

namespace App\Http\Controllers\Settings\Preferences;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Vault\ViewHelpers\VaultIndexViewHelper;
use App\Http\Controllers\Settings\Preferences\ViewHelpers\PreferencesIndexViewHelper;

class PreferencesController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Preferences/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PreferencesIndexViewHelper::data(Auth::user()),
        ]);
    }
}
