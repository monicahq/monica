<?php

namespace App\Traits;

use Laravel\Cashier\Billable;
use App\Helpers\InstanceHelper;

trait Subscription
{
    use Billable, StripeCall;

    /**
     * Process the upgrade payment.
     *
     * @param  string  $payment_method
     * @param  string  $planName
     * @return bool|string
     */
    public function subscribe(string $payment_method, string $planName)
    {
        $plan = InstanceHelper::getPlanInformationFromConfig($planName);
        if ($plan === null) {
            abort(404);
        }

        return $this->stripeCall(function () use ($payment_method, $plan) {
            $this->newSubscription($plan['name'], $plan['id'])
                        ->create($payment_method, [
                            'email' => auth()->user()->email,
                        ]);

            return true;
        });
    }

    /**
     * Update an existing subscription.
     *
     * @param  string  $planName
     * @param  \Laravel\Cashier\Subscription  $subscription
     * @return \Laravel\Cashier\Subscription
     */
    public function updateSubscription(string $planName, \Laravel\Cashier\Subscription $subscription)
    {
        $oldPlan = $subscription->stripe_price;
        $plan = InstanceHelper::getPlanInformationFromConfig($planName);
        if ($plan === null) {
            abort(404);
        }

        if ($oldPlan === $planName) {
            // No change
            return $subscription;
        }

        $subscription = $this->stripeCall(function () use ($subscription, $plan) {
            return $subscription->swap($plan['id']);
        });

        if ($subscription->stripe_price !== $oldPlan && $subscription->stripe_price === $plan['id']) {
            $subscription->forceFill([
                'name' => $plan['name'],
            ])->save();
        }

        return $subscription;
    }

    /**
     * Check if the account is currently subscribed to a plan.
     *
     * @return bool
     */
    public function isSubscribed()
    {
        if ($this->has_access_to_paid_version_for_free) {
            return true;
        }

        return $this->getSubscribedPlan() !== null;
    }

    /**
     * Get the subscription the account is subscribed to.
     *
     * @return \Laravel\Cashier\Subscription|null
     */
    public function getSubscribedPlan()
    {
        return $this->subscriptions()->recurring()->first();
    }

    /**
     * Get the id of the plan the account is subscribed to.
     *
     * @return string
     */
    public function getSubscribedPlanId()
    {
        $plan = $this->getSubscribedPlan();

        return is_null($plan) ? '' : $plan->stripe_price;
    }

    /**
     * Get the friendly name of the plan the account is subscribed to.
     *
     * @return string|null
     */
    public function getSubscribedPlanName(): ?string
    {
        $plan = $this->getSubscribedPlan();

        return is_null($plan) ? null : $plan->name;
    }

    /**
     * Cancel the plan the account is subscribed to.
     *
     * @return bool|string
     */
    public function subscriptionCancel()
    {
        $plan = $this->getSubscribedPlan();

        if (! is_null($plan)) {
            return $this->stripeCall(function () use ($plan) {
                $plan->cancelNow();

                return true;
            });
        }

        return false;
    }

    /**
     * Check if the account has invoices linked to this account.
     *
     * @return bool
     */
    public function hasInvoices()
    {
        return $this->subscriptions()->count() > 0;
    }
}
