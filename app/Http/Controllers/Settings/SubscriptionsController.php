<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Helpers\InstanceHelper;
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

        if (! auth()->user()->account->isSubscribed()) {
            return view('settings.subscriptions.blank', [
                'numberOfCustomers' => InstanceHelper::getNumberOfPaidSubscribers(),
            ]);
        }

        $planId = auth()->user()->account->getSubscribedPlanId();

        return view('settings.subscriptions.account', [
            'planInformation' => InstanceHelper::getPlanInformationFromConfig($planId),
            'nextBillingDate' => auth()->user()->account->getNextBillingDate(),
        ]);
    }

    /**
     * Display the upgrade view page.
     *
     * @return \Illuminate\Http\Response
     */
    public function upgrade(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect('settings/');
        }

        if (auth()->user()->account->isSubscribed()) {
            return redirect('/settings/subscriptions');
        }

        $plan = $request->query('plan');

        return view('settings.subscriptions.upgrade', [
            'planInformation' => InstanceHelper::getPlanInformationFromConfig($plan),
            'nextTheoriticalBillingDate' => DateHelper::getShortDate(DateHelper::getNextTheoriticalBillingDate($plan)),
        ]);
    }

    /**
     * Display the upgrade success page.
     *
     * @return \Illuminate\Http\Response
     */
    public function upgradeSuccess(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect('settings/');
        }

        return view('settings.subscriptions.success');
    }

    /**
     * Display the downgrade success page.
     *
     * @return \Illuminate\Http\Response
     */
    public function downgradeSuccess(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect('settings/');
        }

        return view('settings.subscriptions.downgrade-success');
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

        if (! auth()->user()->account->isSubscribed()) {
            return redirect('/settings');
        }

        return view('settings.subscriptions.downgrade-checklist');
    }

    /**
     * Process the downgrade process.
     *
     * @return \Illuminate\Http\Response
     */
    public function processDowngrade()
    {
        if (! auth()->user()->account->canDowngrade()) {
            return redirect('/settings/users/subscriptions/downgrade');
        }

        if (! auth()->user()->account->isSubscribed()) {
            return redirect('/settings');
        }

        auth()->user()->account->subscription(auth()->user()->account->getSubscribedPlanName())->cancelNow();

        return redirect('/settings/subscriptions/downgrade/success');
    }

    /**
     * Process the upgrade payment.
     *
     * @return \Illuminate\Http\Response
     */
    public function processPayment(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect('settings/');
        }

        $stripeToken = $request->input('stripeToken');

        $plan = InstanceHelper::getPlanInformationFromConfig($request->input('plan'));

        auth()->user()->account->newSubscription($plan['name'], $plan['id'])
                    ->create($stripeToken, [
                        'email' => auth()->user()->email,
                    ]);

        return redirect('settings/subscriptions/upgrade/success');
    }

    /**
     * Download the invoice as PDF.
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadInvoice(Request $request, $invoiceId)
    {
        return auth()->user()->account->downloadInvoice($invoiceId, [
            'vendor'  => 'Monica',
            'product' => trans('settings.subscriptions_pdf_title', ['name' => config('monica.paid_plan_monthly_friendly_name')]),
        ]);
    }
}
