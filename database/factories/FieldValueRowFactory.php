<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValueRow;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<FieldValueRow> */
class FieldValueRowFactory extends Factory
{
    protected $model = FieldValueRow::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'valuable_type' => Contact::class,
            'valuable_id' => Contact::factory(),
            'account_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['valuable_id'])->account_id,
            'field_id' => Field::factory()->repeater(),
            'position' => 1,
        ];
    }
}
