<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\GenerateTelegramWebhookSecret;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramWebhookSecretController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        new GenerateTelegramWebhookSecret(
            administrator: $request->user(),
        )->execute();

        return redirect()->route('admin.configuration.index')
            ->with('status', __('A new secret is set. It is never shown back.'));
    }
}
