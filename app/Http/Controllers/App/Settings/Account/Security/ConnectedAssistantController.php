<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\DisconnectAssistant;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConnectedAssistantController extends Controller
{
    public function destroy(Request $request, string $client): RedirectResponse
    {
        new DisconnectAssistant(
            user: $request->user(),
            clientId: $client,
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('The assistant is disconnected.'))
            ->with('status_description', __('It has to ask you again before it can reach your account.'));
    }
}
