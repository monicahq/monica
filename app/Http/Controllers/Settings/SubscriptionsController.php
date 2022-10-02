<?php

namespace App\Http\Controllers\Settings;

use Exception;
use Illuminate\View\View;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Helpers\AccountHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Subscription\ActivateLicenceKey;

class SubscriptionsController extends Controller
{
    public function index()
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        $account = auth()->user()->account;

        if ($account->is_on_stripe) {
            return view('settings.subscriptions.stripe', [
                'customerPortalUrl' => config('monica.customer_portal_stripe_url'),
                'accountHasLimitations' => AccountHelper::hasLimitations($account),
            ]);
        }

        if (! $account->isSubscribed()) {
            return view('settings.subscriptions.blank', [
                'customerPortalUrl' => config('monica.customer_portal_url'),
            ]);
        }

        return view('settings.subscriptions.account', [
            'planInformation' => trans('settings.subscriptions_licence_key_frequency_'.$account->frequency),
            'nextBillingDate' => DateHelper::getFullDate($account->valid_until_at),
            'accountHasLimitations' => AccountHelper::hasLimitations($account),
            'customerPortalUrl' => config('monica.customer_portal_url'),
        ]);
    }

    public function store(Request $request)
    {
        try {
            ActivateLicenceKey::dispatchSync([
                'account_id' => auth()->user()->account_id,
                'licence_key' => $request->input('licence_key'),
            ]);
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->validator);
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->withErrors($e->getMessage());
        }

        return view('settings.subscriptions.success');
    }
}
