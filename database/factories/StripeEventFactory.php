<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StripeEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<StripeEvent> */
class StripeEventFactory extends Factory
{
    protected $model = StripeEvent::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'stripe_id' => 'evt_'.Str::lower(Str::random(16)),
            'type' => 'invoice.paid',
        ];
    }
}
