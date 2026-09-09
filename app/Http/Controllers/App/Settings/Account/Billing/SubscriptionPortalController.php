<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Billing;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionPortalController extends Controller
{
    public function show(Request $request): RedirectResponse
    {
        $account = $request->user()->account;

        abort_unless($account->stripe_id !== null, 404);

        try {
            $url = $account->billingPortalUrl(route('settings.billing.index'));
        } catch (Exception) {
            return redirect()->route('settings.billing.index')
                ->withErrors(['subscription' => __('Stripe did not answer, so its portal could not be opened.')]);
        }

        return redirect()->away($url);
    }
}
