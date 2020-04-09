<?php

namespace App\Http\Controllers\Settings;

use Illuminate\View\View;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Payment;
use App\Helpers\AccountHelper;
use App\Helpers\InstanceHelper;
use App\Exceptions\StripeException;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent as StripePaymentIntent;
use Laravel\Cashier\Exceptions\IncompletePayment;
use App\Services\Account\Settings\ArchiveAllContacts;

class SubscriptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View|Factory|RedirectResponse
     */
    public function index()
    {
        $account = auth()->user()->account;

        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        $subscription = $account->getSubscribedPlan();
        if (! $account->isSubscribed() && (! $subscription || $subscription->ended())) {
            return view('settings.subscriptions.blank', [
                'numberOfCustomers' => InstanceHelper::getNumberOfPaidSubscribers(),
            ]);
        }

        $planId = $account->getSubscribedPlanId();
        try {
            $nextBillingDate = $account->getNextBillingDate();
        } catch (StripeException $e) {
            $nextBillingDate = trans('app.unknown');
        }

        $hasInvoices = $account->hasStripeId() && $account->hasInvoices();
        $invoices = null;
        if ($hasInvoices) {
            $invoices = $account->invoices();
        }

        return view('settings.subscriptions.account', [
            'planInformation' => InstanceHelper::getPlanInformationFromConfig($planId),
            'nextBillingDate' => $nextBillingDate,
            'subscription' => $subscription,
            'hasInvoices' => $hasInvoices,
            'invoices' => $invoices,
            'accountHasLimitations' => AccountHelper::hasLimitations($account),
        ]);
    }

    /**
     * Display the upgrade view page.
     *
     * @param Request $request
     * @return View|Factory|RedirectResponse
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
     * @return View|Factory|RedirectResponse
     * @throws ApiErrorException
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
     * @return View|Factory|RedirectResponse
     */
    public function upgradeSuccess()
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        return view('settings.subscriptions.success');
    }

    /**
     * Display the downgrade success page.
     *
     * @param Request $request
     * @return View|Factory|RedirectResponse
     */
    public function downgradeSuccess(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        return view('settings.subscriptions.downgrade-success');
    }

    /**
     * Display the archive all your contacts page.
     *
     * @return View|Factory|RedirectResponse
     */
    public function archive()
    {
        return view('settings.subscriptions.archive');
    }

    /**
     * Process the Archive process.
     *
     * @return RedirectResponse
     */
    public function processArchive()
    {
        app(ArchiveAllContacts::class)->execute([
            'account_id' => auth()->user()->account_id,
        ]);

        return redirect()->route('settings.subscriptions.downgrade');
    }

    /**
     * Display the downgrade view page.
     *
     * @return View|Factory|RedirectResponse
     */
    public function downgrade()
    {
        $account = auth()->user()->account;

        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        $subscription = $account->getSubscribedPlan();
        if (! $account->isSubscribed() && ! $subscription) {
            return redirect()->route('settings.index');
        }

        return view('settings.subscriptions.downgrade-checklist')
            ->with('numberOfActiveContacts', $account->contacts()->active()->count())
            ->with('numberOfPendingInvitations', $account->invitations()->count())
            ->with('numberOfUsers', $account->users()->count())
            ->with('accountHasLimitations', AccountHelper::hasLimitations($account))
            ->with('hasReachedContactLimit', AccountHelper::hasReachedContactLimit($account))
            ->with('canDowngrade', AccountHelper::canDowngrade($account));
    }

    /**
     * Process the downgrade process.
     *
     * @return RedirectResponse
     */
    public function processDowngrade()
    {
        if (! AccountHelper::canDowngrade(auth()->user()->account)) {
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
     * @param Request $request
     * @return RedirectResponse
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
     * @param mixed $invoiceId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadInvoice($invoiceId)
    {
        return auth()->user()->account->downloadInvoice($invoiceId, [
            'vendor'  => 'Monica',
            'product' => trans('settings.subscriptions_pdf_title', ['name' => config('monica.paid_plan_monthly_friendly_name')]),
        ]);
    }

    /**
     * Download the invoice as PDF.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|null
     */
    public function forceCompletePaymentOnTesting(Request $request): ?RedirectResponse
    {
        if (App::environment('production')) {
            return null;
        }
        $subscription = auth()->user()->account->getSubscribedPlan();
        $subscription->stripe_status = 'active';
        $subscription->save();

        return redirect()->route('settings.subscriptions.index');
    }
}
