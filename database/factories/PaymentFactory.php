<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OfferKindEnum;
use App\Models\Account;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'stripe_id' => 'in_'.Str::lower(Str::random(16)),
            'stripe_subscription_id' => null,
            'kind' => OfferKindEnum::Lifetime,
            'status' => 'paid',
            'total' => 19900,
            'tax' => 3317,
            'currency' => 'eur',
            'invoice_number' => 'DM-'.Str::upper(Str::random(6)),
            'hosted_invoice_url' => 'https://invoice.stripe.com/i/test_dundermifflin',
            'invoice_pdf_url' => 'https://pay.stripe.com/invoice/test_dundermifflin/pdf',
            'billed_at' => now(),
        ];
    }

    public function forSubscription(string $kind = 'monthly'): static
    {
        return $this->state(fn (array $attributes): array => [
            'stripe_subscription_id' => 'sub_'.Str::lower(Str::random(16)),
            'kind' => OfferKindEnum::from($kind),
            'total' => $kind === 'yearly' ? 9000 : 900,
            'tax' => $kind === 'yearly' ? 1500 : 150,
        ]);
    }
}
