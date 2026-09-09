<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Billing;

use App\Actions\ResumeSubscription;
use App\Exceptions\AccountHasNoSubscription;
use App\Exceptions\SubscriptionCannotBeChanged;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionCancellationController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        try {
            new ResumeSubscription(user: $request->user())->execute();
        } catch (AccountHasNoSubscription|SubscriptionCannotBeChanged $e) {
            return redirect()->route('settings.billing.index')->withErrors(['subscription' => $e->getMessage()]);
        }

        return redirect()->route('settings.billing.index')
            ->with('status', __('The subscription keeps running.'))
            ->with('status_description', __('It renews as before, and nothing changes until then.'));
    }
}
