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
        if (! config('monica.requires_subscription')) {
            return redirect('settings/');
        }

        return view('settings.subscriptions.account');
    }

    /**
     * Display the upgrade view page.
     *
     * @return \Illuminate\Http\Response
     */
    public function upgrade()
    {
        if (! config('monica.requires_subscription')) {
            return redirect('settings/');
        }

        $account = auth()->user()->account;

        if ($account->isSubscribed()) {
            return redirect('/settings/subscriptions');
        }

        return view('settings.subscriptions.upgrade');
    }

    /**
     * Display the downgrade view page.
     *
     * @return \Illuminate\Http\Response
     */
    public function downgrade()
    {
        if (! config('monica.requires_subscription')) {
            return redirect('settings/');
        }

        if (! auth()->user()->account->subscribed(config('monica.paid_plan_friendly_name'))) {
            return redirect('/settings');
        }

        return view('settings.subscriptions.downgrade-checklist');
    }

    /**
     * Process the downgrade process
     *
     * @return \Illuminate\Http\Response
     */
    public function processDowngrade()
    {
        if (! auth()->user()->account->canDowngrade()) {
            return redirect('/settings/users/subscriptions/downgrade');
        }

        auth()->user()->account->subscription(config('monica.paid_plan_friendly_name'))->cancelNow();

        return redirect('/settings/subscriptions');
    }

    /**
     * Process the upgrade payment
     *
     * @return \Illuminate\Http\Response
     */
    public function processPayment(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect('settings/');
        }

        $stripeToken = $request->input('stripeToken');

        auth()->user()->account->newSubscription(config('monica.paid_plan_friendly_name'), config('monica.paid_plan_id'))
                    ->create($stripeToken);

        return redirect('settings/subscriptions');
    }

    /**
     * Download the invoice as PDF
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadInvoice(Request $request, $invoiceId)
    {
        return auth()->user()->account->downloadInvoice($invoiceId, [
            'vendor'  => 'Monica',
            'product' => trans('settings.subscriptions_pdf_title', ['name' => config('monica.paid_plan_friendly_name')]),
        ]);
    }
}
