<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings;

use App\Http\Controllers\Controller;
use App\ViewModels\Settings\SettingsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.index', [
            'viewModel' => new SettingsViewModel(
                user: $request->user(),
            ),
        ]);
    }
}
