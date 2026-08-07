<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Security\SecurityViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.security.index', [
            'viewModel' => new SecurityViewModel(
                user: $request->user(),
                employee: $request->user()->employee,
            ),
        ]);
    }
}
