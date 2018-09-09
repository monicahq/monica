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
            return redirect()->route('settings.index');
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
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function processDowngrade()
    {
        if (! auth()->user()->account->canDowngrade()) {
            return redirect()->route('settings.subscriptions.downgrade');
        }

        if (! auth()->user()->account->isSubscribed()) {
            return redirect()->route('settings.index');
        }

        auth()->user()->account->subscription(auth()->user()->account->getSubscribedPlanName())->cancelNow();

        return redirect()->route('settings.subscriptions.downgrade.success');
    }

    /**
     * Process the upgrade payment.
     *
     * @return \Illuminate\Http\Response
     */
    public function processPayment(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        $stripeToken = $request->input('stripeToken');

        $plan = InstanceHelper::getPlanInformationFromConfig($request->input('plan'));
        $errorMessage = '';

        try {
            auth()->user()->account->newSubscription($plan['name'], $plan['id'])
                        ->create($stripeToken, [
                            'email' => auth()->user()->email,
                        ]);

            return redirect()->route('settings.subscriptions.upgrade.success');
        } catch (\Stripe\Error\Card $e) {
            // Since it's a decline, \Stripe\Error\Card will be caught
            $body = $e->getJsonBody();
            $err = $body['error'];
            $errorMessage = trans('settings.stripe_error_card', ['message' => $err['message']]);
        } catch (\Stripe\Error\RateLimit $e) {
            // Too many requests made to the API too quickly
            $errorMessage = trans('settings.stripe_error_rate_limit');
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            $errorMessage = trans('settings.stripe_error_authentication');
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            $errorMessage = trans('settings.stripe_error_api_connection_error');
        } catch (\Stripe\Error\Base $e) {
            $errorMessage = $e->getMessage();
        }

        return back()
            ->withInput()
            ->withErrors($errorMessage);
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
