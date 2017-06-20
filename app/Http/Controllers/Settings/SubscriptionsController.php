<?php

namespace App\Http\Controllers\Settings;

use App\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SubscriptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (config('monica.unlock_paid_features')) {
            return redirect('settings/');
        }

        $account = auth()->user()->account;

        if (! $account->subscribed(config('monica.paid_plan_friendly_name'))) {
            return view('settings.subscriptions.upgrade');
        }

        // Weird method to get the next billing date from Laravel Cashier
        $timestamp = $account -> asStripeCustomer()["subscriptions"] -> data[0]["current_period_end"];
        $nextBillingdate = \App\Helpers\DateHelper::getShortDate($timestamp);

        return view('settings.subscriptions.account', compact('account', 'nextBillingdate'));
    }

    /**
     * Show the upgrade page.
     *
     * @return \Illuminate\Http\Response
     */
    public function upgrade()
    {
        if (config('monica.unlock_paid_features')) {
            return redirect('/settings');
        }

        return view('settings.subscriptions.upgrade');
    }

    public function downgrade()
    {
        if (config('monica.unlock_paid_features')) {
            return redirect('settings/');
        }

        if (! auth()->user()->account->subscribed(config('monica.paid_plan_friendly_name'))) {
            return redirect('/settings');
        }

        return view('settings.subscriptions.downgrade-checklist');
    }

    public function processDowngrade()
    {
        if (! auth()->user()->account->canDowngrade()) {
            return redirect('/settings/users/subscriptions/downgrade');
        }

        auth()->user()->account->subscription(config('monica.paid_plan_friendly_name'))->cancel();

        return view('settings.subscriptions.downgrade-checklist');
    }

    public function processPayment(Request $request)
    {
        if (config('monica.unlock_paid_features')) {
            return redirect('settings/');
        }

        $stripeToken = $request->input('stripeToken');

        auth()->user()->account->newSubscription(config('monica.paid_plan_friendly_name'), config('monica.paid_plan_id'))
                    ->create($stripeToken);

        return redirect('settings/subscriptions');
    }

    public function downloadInvoice(Request $request, $invoiceId)
    {
        return auth()->user()->account->downloadInvoice($invoiceId, [
            'vendor'  => 'Monica',
            'product' => 'Your Chandler monthly subscription',
        ]);
    }
}
