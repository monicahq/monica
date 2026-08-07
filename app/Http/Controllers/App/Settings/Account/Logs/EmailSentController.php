<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Logs;

use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Account\Logs\EmailsSentViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailSentController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.logs.emails', [
            'viewModel' => new EmailsSentViewModel(
                user: $request->user(),
                employee: $request->user()->employee,
            ),
        ]);
    }
}
