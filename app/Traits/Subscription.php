<?php

namespace App\Traits;

use App\Helpers\DateHelper;
use Laravel\Cashier\Billable;
use App\Helpers\InstanceHelper;
use App\Exceptions\StripeException;
use Illuminate\Support\Facades\Log;

trait Subscription
{
    use Billable;

    /**
     * Process the upgrade payment.
     *
     * @param string $payment_method
     * @param string $planName
     * @return bool|string
     */
    public function subscribe(string $payment_method, string $planName)
    {
        $plan = InstanceHelper::getPlanInformationFromConfig($planName);

        return $this->stripeCall(function () use ($payment_method, $plan) {
            $this->newSubscription($plan['name'], $plan['id'])
                        ->create($payment_method, [
                            'email' => auth()->user()->email,
                        ]);

            return true;
        });
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

        return $this->subscribed(config('monica.paid_plan_monthly_friendly_name'))
            || $this->subscribed(config('monica.paid_plan_annual_friendly_name'));
    }

    /**
     * Get the subscription the account is subscribed to.
     *
     * @return \Laravel\Cashier\Subscription|null
     */
    public function getSubscribedPlan()
    {
        $subscription = $this->subscription(config('monica.paid_plan_monthly_friendly_name'));

        if (! $subscription) {
            $subscription = $this->subscription(config('monica.paid_plan_annual_friendly_name'));
        }

        return $subscription;
    }

    /**
     * Get the id of the plan the account is subscribed to.
     *
     * @return string
     */
    public function getSubscribedPlanId()
    {
        $plan = $this->getSubscribedPlan();

        if (! is_null($plan)) {
            return $plan->stripe_plan;
        }

        return '';
    }

    /**
     * Get the friendly name of the plan the account is subscribed to.
     *
     * @return string|null
     */
    public function getSubscribedPlanName()
    {
        $plan = $this->getSubscribedPlan();

        if (! is_null($plan)) {
            return $plan->name;
        }
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

    /**
     * Get the next billing date for the account.
     *
     * @return string
     */
    public function getNextBillingDate()
    {
        // Weird method to get the next billing date from Laravel Cashier
        // see https://stackoverflow.com/questions/41576568/get-next-billing-date-from-laravel-cashier
        return $this->stripeCall(function () {
            $subscriptions = $this->asStripeCustomer()['subscriptions'];
            if (! $subscriptions || count($subscriptions->data) <= 0) {
                return '';
            }
            $timestamp = $subscriptions->data[0]['current_period_end'];

            return DateHelper::getFullDate($timestamp);
        });
    }

    /**
     * Call stripe.
     *
     * @param callable $callback
     * @return mixed
     */
    private function stripeCall($callback)
    {
        try {
            return $callback();
        } catch (\Stripe\Error\Card $e) {
            // Since it's a decline, \Stripe\Error\Card will be caught
            $body = $e->getJsonBody();
            $err = $body['error'];
            $errorMessage = trans('settings.stripe_error_card', ['message' => $err['message']]);
            Log::error('Stripe card decline error: '.(string) $e, $e->getJsonBody() ?: []);
        } catch (\Stripe\Error\RateLimit $e) {
            // Too many requests made to the API too quickly
            $errorMessage = trans('settings.stripe_error_rate_limit');
            Log::error('Stripe RateLimit error: '.(string) $e, $e->getJsonBody() ?: []);
        } catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid parameters were supplied to Stripe's API
            $errorMessage = trans('settings.stripe_error_invalid_request');
            Log::error('Stripe InvalidRequest error: '.(string) $e, $e->getJsonBody() ?: []);
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            $errorMessage = trans('settings.stripe_error_authentication');
            Log::error('Stripe Authentication error: '.(string) $e, $e->getJsonBody() ?: []);
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            $errorMessage = trans('settings.stripe_error_api_connection_error');
            Log::error('Stripe ApiConnection error: '.(string) $e, $e->getJsonBody() ?: []);
        } catch (\Stripe\Error\Base $e) {
            $errorMessage = $e->getMessage();
            Log::error('Stripe error: '.(string) $e, $e->getJsonBody() ?: []);
        }

        throw new StripeException($errorMessage);
    }
}
