<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Billing;

use App\Actions\StartCheckout;
use App\Exceptions\AccountAlreadyOwnsTheProduct;
use App\Exceptions\ProviderUnreachable;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function store(Request $request, string $price): RedirectResponse
    {
        $account = $request->user()->account;
        $currency = $request->session()->get('billing.currency');

        try {
            $checkout = new StartCheckout(
                account: $request->user()->account,
                priceId: $price,
                currency: $currency,
            )->execute();
        } catch (AccountAlreadyOwnsTheProduct $e) {
            return back()->withErrors(['offer' => __('That plan is not one this account can take up.')]);
        } catch (ProviderUnreachable $e) {
            Log::error('CheckoutController: Provider unreachable: '.$e->getMessage(), [
                'accountId' => $account->id,
                'stripeId' => $account->stripe_id,
                'priceId' => $price,
                'currency' => $currency,
                'exception' => $e,
            ]);

            return back()->withErrors(['offer' => __('We could not reach Stripe just now. Nothing was charged. Please try again in a moment.')]);
        }

        return $checkout->redirect();
    }
}
