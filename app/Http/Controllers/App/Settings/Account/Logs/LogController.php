<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Logs;

use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Logs\LogsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.logs.index', [
            'viewModel' => new LogsViewModel(
                user: $request->user(),
                employee: $request->user()->employee,
            ),
        ]);
    }
}
