<?php

namespace App\Http\Controllers\Settings;

use Inertia\Inertia;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Vault\ManageVault\VaultIndexViewHelper;

class SettingsController extends Controller
{
    /**
     * Show the settings page.
     *
     * @return Response
     */
    public function index()
    {
        return Inertia::render('Settings/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => SettingsIndexViewHelper::data(Auth::user()->account),
        ]);
    }
}
