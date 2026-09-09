<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'payment',
            'id' => $this->stripe_id,
            'attributes' => [
                'provider_id' => $this->stripe_id,
                'offer' => $this->kind?->value,
                'status' => $this->status,
                'total' => $this->total,
                'tax' => $this->tax,
                'currency' => $this->currency,
                'invoice_number' => $this->invoice_number,
                'invoice_url' => $this->hosted_invoice_url,
                'invoice_pdf_url' => $this->invoice_pdf_url,
                'billed_at' => $this->billed_at->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.billing.payments.show', $this->stripe_id),
            ],
        ];
    }
}
