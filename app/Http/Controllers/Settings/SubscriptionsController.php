<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Payment;
use App\Helpers\InstanceHelper;
use App\Exceptions\StripeException;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Stripe\PaymentIntent as StripePaymentIntent;
use Laravel\Cashier\Exceptions\IncompletePayment;

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

        $subscription = auth()->user()->account->getSubscribedPlan();
        if (! auth()->user()->account->isSubscribed() && (! $subscription || $subscription->ended())) {
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

        $hasInvoices = auth()->user()->account->hasStripeId() && auth()->user()->account->hasInvoices();
        $invoices = null;
        if ($hasInvoices) {
            $invoices = auth()->user()->account->invoices();
        }

        return view('settings.subscriptions.account', [
            'planInformation' => InstanceHelper::getPlanInformationFromConfig($planId),
            'nextBillingDate' => $nextBillingDate,
            'subscription' => $subscription,
            'hasInvoices' => $hasInvoices,
            'invoices' => $invoices,
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
            'nextTheoriticalBillingDate' => DateHelper::getFullDate(DateHelper::getNextTheoriticalBillingDate($plan)),
            'intent' => auth()->user()->account->createSetupIntent(),
        ]);
    }

    /**
     * Display the confirm view page.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
     */
    public function confirmPayment($id)
    {
        return view('settings.subscriptions.confirm', [
            'payment' => new Payment(
                StripePaymentIntent::retrieve($id, Cashier::stripeOptions())
            ),
            'redirect' => request('redirect'),
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

        $subscription = auth()->user()->account->getSubscribedPlan();
        if (! auth()->user()->account->isSubscribed() && ! $subscription) {
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

        $subscription = auth()->user()->account->getSubscribedPlan();
        if (! auth()->user()->account->isSubscribed() && ! $subscription) {
            return redirect()->route('settings.index');
        }

        try {
            auth()->user()->account->subscriptionCancel();
        } catch (StripeException $e) {
            return back()
                ->withInput()
                ->withErrors($e->getMessage());
        }

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
                ->subscribe($request->input('payment_method'), $request->input('plan'));
        } catch (IncompletePayment $e) {
            return redirect()->route(
                'settings.subscriptions.confirm',
                [$e->payment->asStripePaymentIntent()->id, 'redirect' => route('settings.subscriptions.upgrade.success')]
            );
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

    /**
     * Download the invoice as PDF.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function forceCompletePaymentOnTesting(Request $request)
    {
        if (App::environment('production')) {
            return;
        }
        $subscription = auth()->user()->account->getSubscribedPlan();
        $subscription->stripe_status = 'active';
        $subscription->save();

        return redirect()->route('settings.subscriptions.index');
    }
}
