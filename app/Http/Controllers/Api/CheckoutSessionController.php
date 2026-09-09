<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\StartCheckout;
use App\Exceptions\AccountAlreadyOwnsTheProduct;
use App\Exceptions\ProviderUnreachable;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'price_id' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        try {
            $checkout = new StartCheckout(
                account: $request->user()->account,
                priceId: $validated['price_id'],
                currency: $validated['currency'] ?? null,
            )->execute();
        } catch (AccountAlreadyOwnsTheProduct $e) {
            throw ValidationException::withMessages(['price_id' => $e->getMessage()]);
        } catch (ProviderUnreachable $e) {
            abort(503, $e->getMessage());
        }

        return response()->json([
            'data' => [
                'type' => 'checkout_session',
                'attributes' => [
                    'url' => $checkout->asStripeCheckoutSession()->url,
                ],
            ],
        ], 201);
    }
}
