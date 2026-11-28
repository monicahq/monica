<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\EndOAuthConnection;
use App\Http\Controllers\Controller;
use App\Models\OAuthConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OAuthConnectionController extends Controller
{
    public function destroy(Request $request, OAuthConnection $oauthConnection): RedirectResponse
    {
        abort_unless($oauthConnection->ended_at === null, 404);

        new EndOAuthConnection(
            user: $request->user(),
            connection: $oauthConnection,
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __(':name is disconnected.', ['name' => $oauthConnection->name]))
            ->with('status_description', __('It has to ask you again before it can reach your account.'));
    }
}
