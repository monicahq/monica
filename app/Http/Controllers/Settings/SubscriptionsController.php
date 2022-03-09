<?php

namespace App\Http\Controllers\Settings;

use App\Exceptions\NoLicenceKeyEncryptionSetException;
use Illuminate\View\View;
use App\Traits\StripeCall;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Payment;
use App\Helpers\AccountHelper;
use App\Helpers\InstanceHelper;
use App\Exceptions\StripeException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent as StripePaymentIntent;
use Laravel\Cashier\Exceptions\IncompletePayment;
use App\Services\Account\Settings\ArchiveAllContacts;
use App\Services\Account\Subscription\ActivateLicenceKey;
use Exception;

class SubscriptionsController extends Controller
{
    use StripeCall;

    /**
     * Display a listing of the resource.
     *
     * @return View|Factory|RedirectResponse
     */
    public function index()
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        $account = auth()->user()->account;

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
            app(ActivateLicenceKey::class)->execute([
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

    /**
     * Display the upgrade view page.
     *
     * @param  Request  $request
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
        if ($plan !== 'monthly' && $plan !== 'annual') {
            abort(404);
        }

        $planInformation = InstanceHelper::getPlanInformationFromConfig($plan);

        if ($planInformation === null) {
            abort(404);
        }

        return view('settings.subscriptions.upgrade', [
            'planInformation' => $planInformation,
            'nextTheoriticalBillingDate' => DateHelper::getFullDate(DateHelper::getNextTheoriticalBillingDate($plan)),
            'intent' => auth()->user()->account->createSetupIntent(),
        ]);
    }

    /**
     * Display the update view page.
     *
     * @param  Request  $request
     * @return View|Factory|RedirectResponse
     */
    public function update(Request $request)
    {
        if (! config('monica.requires_subscription')) {
            return redirect()->route('settings.index');
        }

        $account = auth()->user()->account;

        $subscription = $account->getSubscribedPlan();
        if (! $account->isSubscribed() && (! $subscription || $subscription->ended())) {
            return view('settings.subscriptions.blank');
        }

        $planInformation = InstanceHelper::getPlanInformationFromSubscription($subscription);

        if ($planInformation === null) {
            abort(404);
        }

        $plans = collect();
        foreach (['monthly', 'annual'] as $plan) {
            $plans->push(InstanceHelper::getPlanInformationFromConfig($plan));
        }

        $legacyPlan = null;
        if (! $plans->contains(function ($value) use ($planInformation) {
            return $value['id'] === $planInformation['id'];
        })) {
            $legacyPlan = $planInformation;
        }

        return view('settings.subscriptions.update', [
            'planInformation' => $planInformation,
            'plans' => $plans,
            'legacyPlan' => $legacyPlan,
        ]);
    }

    /**
     * Process the update process.
     *
     * @param  Request  $request
     * @return View|Factory|RedirectResponse
     */
    public function processUpdate(Request $request)
    {
        $account = auth()->user()->account;

        $subscription = $account->getSubscribedPlan();
        if (! $account->isSubscribed() && ! $subscription) {
            return redirect()->route('settings.index');
        }

        try {
            $account->updateSubscription($request->input('frequency'), $subscription);
        } catch (StripeException $e) {
            return back()
                ->withInput()
                ->withErrors($e->getMessage());
        }

        return redirect()->route('settings.subscriptions.index');
    }

    /**
     * Display the confirm view page.
     *
     * @return View|Factory|RedirectResponse
     *
     * @throws ApiErrorException
     */
    public function confirmPayment($id)
    {
        try {
            $payment = $this->stripeCall(function () use ($id) {
                return StripePaymentIntent::retrieve($id, Cashier::stripeOptions());
            });
        } catch (StripeException $e) {
            return back()->withErrors($e->getMessage());
        }

        return view('settings.subscriptions.confirm', [
            'payment' => new Payment($payment),
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
     * @param  Request  $request
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
            ->with('numberOfActiveContacts', $account->allContacts()->active()->count())
            ->with('numberOfPendingInvitations', $account->invitations()->count())
            ->with('numberOfUsers', $account->users()->count())
            ->with('accountHasLimitations', AccountHelper::hasLimitations($account))
            ->with('hasReachedContactLimit', ! AccountHelper::isBelowContactLimit($account))
            ->with('canDowngrade', AccountHelper::canDowngrade($account));
    }

    /**
     * Process the upgrade payment.
     *
     * @param  Request  $request
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
     * @param  mixed  $invoiceId
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
     * @param  Request  $request
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
