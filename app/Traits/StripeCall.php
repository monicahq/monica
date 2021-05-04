<?php

namespace App\Traits;

use App\Exceptions\StripeException;
use Illuminate\Support\Facades\Log;

trait StripeCall
{
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
        } catch (\Stripe\Exception\CardException $e) {
            // Since it's a decline, \Stripe\Error\Card will be caught
            $body = $e->getJsonBody();
            $err = $body['error'];
            $errorMessage = trans('settings.stripe_error_card', ['message' => $err['message']]);
            Log::error('Stripe card decline error: '.(string) $e, $e->getJsonBody() ?: []);
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Too many requests made to the API too quickly
            $errorMessage = trans('settings.stripe_error_rate_limit');
            Log::error('Stripe RateLimit error: '.(string) $e, $e->getJsonBody() ?: []);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            $errorMessage = trans('settings.stripe_error_invalid_request');
            Log::error('Stripe InvalidRequest error: '.(string) $e, $e->getJsonBody() ?: []);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            $errorMessage = trans('settings.stripe_error_authentication');
            Log::error('Stripe Authentication error: '.(string) $e, $e->getJsonBody() ?: []);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Network communication with Stripe failed
            $errorMessage = trans('settings.stripe_error_api_connection_error');
            Log::error('Stripe ApiConnection error: '.(string) $e, $e->getJsonBody() ?: []);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $errorMessage = $e->getMessage();
            Log::error('Stripe error: '.(string) $e, $e->getJsonBody() ?: []);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('Stripe error: '.(string) $e);
        }

        throw new StripeException($errorMessage);
    }
}
