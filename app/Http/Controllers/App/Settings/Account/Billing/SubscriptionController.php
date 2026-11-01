<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Billing;

use App\Actions\CancelSubscription;
use App\Actions\UpgradeSubscription;
use App\Exceptions\AccountHasNoSubscription;
use App\Exceptions\SubscriptionCannotBeChanged;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function update(Request $request, string $price): RedirectResponse
    {
        try {
            new UpgradeSubscription(user: $request->user(), priceId: $price)->execute();
        } catch (AccountHasNoSubscription|SubscriptionCannotBeChanged $e) {
            return redirect()->route('settings.billing.index')->withErrors(['subscription' => $e->getMessage()]);
        }

        return redirect()->route('settings.billing.index')
            ->with('status', __('The subscription moves up at its next renewal.'))
            ->with('status_description', __('Nothing was charged today. The new price is billed from the renewal that was already planned, and the date has not moved.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        try {
            $subscription = new CancelSubscription(user: $request->user())->execute();
        } catch (AccountHasNoSubscription $e) {
            return back()->withErrors(['subscription' => $e->getMessage()]);
        }

        return redirect()->route('settings.billing.index')
            ->with('status', __('The subscription will not renew.'))
            ->with('status_description', __('Full access stays until :date, and nothing is deleted after it.', ['date' => $subscription->ends_at?->isoFormat('LL')]));
    }
}
