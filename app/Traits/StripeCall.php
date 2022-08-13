<?php

namespace App\Traits;

use App\Exceptions\StripeException;
use Illuminate\Support\Facades\Log;

trait StripeCall
{
    /**
     * Call stripe.
     *
     * @template TValue
     *
     * @param  (callable(): TValue)  $callback
     * @return TValue
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
            Log::error(__CLASS__.' '.__FUNCTION__.': Stripe card decline error: '.$e->getMessage(), ['body' => $e->getJsonBody(), $e]);
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Too many requests made to the API too quickly
            $errorMessage = trans('settings.stripe_error_rate_limit');
            Log::error(__CLASS__.' '.__FUNCTION__.': Stripe RateLimit error: '.$e->getMessage(), ['body' => $e->getJsonBody(), $e]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            $errorMessage = trans('settings.stripe_error_invalid_request');
            Log::error(__CLASS__.' '.__FUNCTION__.': Stripe InvalidRequest error: '.$e->getMessage(), ['body' => $e->getJsonBody(), $e]);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            $errorMessage = trans('settings.stripe_error_authentication');
            Log::error(__CLASS__.' '.__FUNCTION__.': Stripe Authentication error: '.$e->getMessage(), ['body' => $e->getJsonBody(), $e]);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Network communication with Stripe failed
            $errorMessage = trans('settings.stripe_error_api_connection_error');
            Log::error(__CLASS__.' '.__FUNCTION__.': Stripe ApiConnection error: '.$e->getMessage(), ['body' => $e->getJsonBody(), $e]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $errorMessage = $e->getMessage();
            Log::error(__CLASS__.' '.__FUNCTION__.': Stripe error: '.$e->getMessage(), ['body' => $e->getJsonBody(), $e]);
        } catch (\Laravel\Cashier\Exceptions\IncompletePayment $e) {
            throw $e;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error(__CLASS__.' '.__FUNCTION__.': Stripe error: '.$e->getMessage(), [$e]);
        }

        throw new StripeException($errorMessage);
    }
}
