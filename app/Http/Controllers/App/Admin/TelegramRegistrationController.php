<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Actions\RegisterTelegramWebhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramRegistrationController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $action = new RegisterTelegramWebhook(
            administrator: $request->user(),
        );
        $registration = $action->execute();

        $description = __('It calls :address.', ['address' => $registration->url]);

        if ($action->differingBotAddress !== null) {
            $description .= ' '.__('Telegram reports the bot at :address, which differs from the bot address set here.', [
                'address' => $action->differingBotAddress,
            ]);
        }

        return redirect()->route('admin.configuration.index')
            ->with('status', __('Telegram now sends this instance what people say to the bot.'))
            ->with('status_description', $description);
    }
}
