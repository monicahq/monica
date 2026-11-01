<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Account;
use App\Models\StripeOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Account */
class BillingResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $payment = $this->payments()->where('status', 'paid')->latest('billed_at')->first();
        $subscription = $this->subscription();
        $order = $this->orders->first(fn (StripeOrder $order): bool => $order->stripe_status !== StripeOrder::UNPAID);

        return [
            'type' => 'billing',
            'attributes' => [
                'has_full_access' => $this->hasFullAccess(),
                'access_kind' => $this->accessKind()?->value,
                'renews_at' => $this->nextBillingDate()?->timestamp,
                'scheduled_offer' => $this->scheduledOfferKind()?->value,
                'ends_at' => $subscription?->onGracePeriod() === true ? $subscription->ends_at?->timestamp : null,
                'purchased_at' => $order?->created_at->timestamp,
                'order' => $order === null ? null : [
                    'provider_id' => $order->stripe_id,
                    'status' => $order->stripe_status,
                    'offer' => $order->offerKind()?->value,
                    'price_id' => $order->stripe_price,
                    'ordered_at' => $order->created_at->timestamp,
                    'settled_at' => $order->isSettled() ? $order->updated_at?->timestamp : null,
                ],
                'billing_email' => $this->billing_email,
                'limits' => [
                    'vaults' => $this->vaultLimit(),
                    'contacts' => $this->contactLimit(),
                    'storage_in_bytes' => $this->storageLimitInBytes(),
                ],
                'usage' => [
                    'vaults' => $this->vaults()->count(),
                    'contacts' => $this->contactCount(),
                    'storage_in_bytes' => $this->storage_used_in_bytes,
                ],
                'payment' => $payment === null ? null : [
                    'provider_id' => $payment->stripe_id,
                    'offer' => $payment->kind?->value,
                    'status' => $payment->status,
                    'total' => $payment->total,
                    'currency' => $payment->currency,
                    'invoice_number' => $payment->invoice_number,
                    'billed_at' => $payment->billed_at->timestamp,
                ],
                'offers_available' => $this->offersAvailable(),
                'offers' => array_map(
                    fn (array $offer): array => [
                        'price_id' => $offer['price_id'],
                        'kind' => $offer['kind']->value,
                        'renews' => $offer['kind']->renews(),
                        'amount' => $offer['amount'],
                        'currency' => $offer['currency'],
                        'prices' => array_map(
                            fn (string $currency, int $amount): array => ['currency' => $currency, 'amount' => $amount],
                            array_keys($offer['prices']),
                            array_values($offer['prices']),
                        ),
                    ],
                    $this->availableOffers(),
                ),
            ],
            'links' => [
                'self' => route('api.v1.billing'),
            ],
        ];
    }
}
