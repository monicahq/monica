<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\CheckTelegramWebhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramRegistrationCheckController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('admin.configuration.index')
            ->with('telegram_check', new CheckTelegramWebhook()->execute());
    }
}
