<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CancelSubscription;
use App\Actions\UpgradeSubscription;
use App\Exceptions\AccountHasNoSubscription;
use App\Exceptions\SubscriptionCannotBeChanged;
use App\Http\Controllers\Controller;
use App\Http\Resources\BillingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscriptionController extends Controller
{
    public function update(Request $request): BillingResource|JsonResponse
    {
        $validated = $request->validate([
            'price_id' => ['required', 'string', 'max:255'],
        ]);

        try {
            new UpgradeSubscription(user: $request->user(), priceId: $validated['price_id'])->execute();
        } catch (AccountHasNoSubscription|SubscriptionCannotBeChanged $e) {
            throw ValidationException::withMessages(['price_id' => $e->getMessage()]);
        } catch (IncompletePayment $e) {
            return response()->json([
                'message' => __('The payment needs confirming before the subscription can move up.'),
                'payment_url' => route('cashier.payment', [$e->payment->asStripePaymentIntent()->id]),
            ], 402);
        }

        return new BillingResource($request->user()->account->refresh());
    }

    public function destroy(Request $request): Response
    {
        try {
            new CancelSubscription(user: $request->user())->execute();
        } catch (AccountHasNoSubscription $e) {
            throw ValidationException::withMessages(['subscription' => $e->getMessage()]);
        }

        return response()->noContent();
    }
}
