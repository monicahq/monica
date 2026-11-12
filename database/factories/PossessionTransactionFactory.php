<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionTransaction> */
class PossessionTransactionFactory extends Factory
{
    protected $model = PossessionTransaction::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'possession_id' => Possession::factory(),
            'account_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->vault_id,
            'type' => PossessionTransactionTypeEnum::Purchase,
            'occurred_on' => '2005-03-14',
            'counterparty' => fake()->randomElement(['Dunder Mifflin', 'Staples', 'Vance Refrigeration', 'Schrute Farms']),
            'amount' => '40.00',
            'tax' => null,
            'fees' => null,
            'shipping' => null,
            'entered_total' => null,
            'currency' => CurrencyEnum::UsDollar,
            'reference' => null,
            'source_url' => null,
            'note' => null,
        ];
    }
}
