<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccountImport;
use App\Models\AccountImportItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<AccountImportItem> */
class AccountImportItemFactory extends Factory
{
    protected $model = AccountImportItem::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_import_id' => AccountImport::factory(),
            'account_id' => fn (array $attributes): int => AccountImport::query()->findOrFail($attributes['account_import_id'])->account_id,
            'vault_source_uuid' => null,
            'kind' => 'contact',
            'source_uuid' => fake()->uuid(),
            'position' => fake()->numberBetween(1, 10000),
            'payload' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
            ],
            'outcome' => null,
            'reason' => null,
            'record_type' => null,
            'record_id' => null,
        ];
    }
}
