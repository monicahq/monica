<?php

namespace App\Traits;

use App\Helpers\DateHelper;
use Laravel\Cashier\Billable;
use App\Helpers\InstanceHelper;
use Illuminate\Support\Facades\DB;
use App\Exceptions\StripeException;
use Illuminate\Support\Facades\Log;

trait Subscription
{
    use Billable;

    /**
     * Process the upgrade payment.
     *
     * @param string $stripeToken
     * @param string $planName
     * @return bool|string
     */
    public function subscribe(string $stripeToken, string $planName)
    {
        $plan = InstanceHelper::getPlanInformationFromConfig($planName);

        return $this->stripeCall(function () use ($stripeToken, $plan) {
            $this->newSubscription($plan['name'], $plan['id'])
                        ->create($stripeToken, [
                            'email' => auth()->user()->email,
                        ]);

            return true;
        });
    }

    /**
     * Get the id of the plan the account is subscribed to.
     *
     * @return string
     */
    public function getSubscribedPlanId()
    {
        $plan = $this->subscriptions()->first();

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
        $plan = $this->subscriptions()->first();

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
        $plan = $this->subscriptions()->first();

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
     * This was created because Laravel Cashier doesn't know how to properly
     * handled the case when a user doesn't have invoices yet. This sucks balls.
     *
     * @return bool
     */
    public function hasInvoices()
    {
        $query = DB::table('subscriptions')->where('account_id', $this->id)->count();

        return $query > 0;
    }

    /**
     * Get the next billing date for the account.
     *
     * @return string $timestamp
     */
    public function getNextBillingDate()
    {
        // Weird method to get the next billing date from Laravel Cashier
        // see https://stackoverflow.com/questions/41576568/get-next-billing-date-from-laravel-cashier
        return $this->stripeCall(function () {
            $subscriptions = $this->asStripeCustomer()['subscriptions'];
            if (count($subscriptions->data) <= 0) {
                return '';
            }
            $timestamp = $subscriptions->data[0]['current_period_end'];

            return DateHelper::getShortDate($timestamp);
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
