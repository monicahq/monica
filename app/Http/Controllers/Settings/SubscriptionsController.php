<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Helpers\InstanceHelper;
use App\Exceptions\StripeException;
use App\Http\Controllers\Controller;

class SubscriptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        if (! auth()->user()->account->isSubscribed()) {
            return view('settings.subscriptions.blank', [
                'numberOfCustomers' => InstanceHelper::getNumberOfPaidSubscribers(),
            ]);
        }

        $planId = auth()->user()->account->getSubscribedPlanId();
        try {
            $nextBillingDate = auth()->user()->account->getNextBillingDate();
        } catch (StripeException $e) {
            $nextBillingDate = trans('app.unknown');
        }

        return view('settings.subscriptions.account', [
            'planInformation' => InstanceHelper::getPlanInformationFromConfig($planId),
            'nextBillingDate' => $nextBillingDate,
        ]);
    }

    /**
     * Display the upgrade view page.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
     */
    public function upgrade(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        if (auth()->user()->account->isSubscribed()) {
            return redirect()->route('settings.subscriptions.index');
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
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
     */
    public function upgradeSuccess(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        return view('settings.subscriptions.success');
    }

    /**
     * Display the downgrade success page.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
     */
    public function downgradeSuccess(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        return view('settings.subscriptions.downgrade-success');
    }

    /**
     * Display the downgrade view page.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
     */
    public function downgrade()
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        if (! auth()->user()->account->isSubscribed()) {
            return redirect()->route('settings.index');
        }

        return view('settings.subscriptions.downgrade-checklist');
    }

    /**
     * Process the downgrade process.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processDowngrade()
    {
        if (! auth()->user()->account->canDowngrade()) {
            return redirect()->route('settings.subscriptions.downgrade');
        }

        if (! auth()->user()->account->isSubscribed()) {
            return redirect()->route('settings.index');
        }

        auth()->user()->account->subscriptionCancel();

        return redirect()->route('settings.subscriptions.downgrade.success');
    }

    /**
     * Process the upgrade payment.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processPayment(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        try {
            auth()->user()->account
                ->subscribe($request->input('stripeToken'), $request->input('plan'));
        } catch (StripeException $e) {
            return back()
                ->withInput()
                ->withErrors($e->getMessage());
        }

        return redirect()->route('settings.subscriptions.upgrade.success');
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
