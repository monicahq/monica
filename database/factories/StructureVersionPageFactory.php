<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<StructureVersionPage> */
class StructureVersionPageFactory extends Factory
{
    protected $model = StructureVersionPage::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        $label = Str::ucfirst(fake()->unique()->word());

        return [
            'structure_version_id' => StructureVersion::factory(),
            'account_id' => fn (array $attributes): int => StructureVersion::query()->findOrFail($attributes['structure_version_id'])->account_id,
            'key' => Str::slug($label),
            'label' => $label,
            'label_translation_key' => null,
            'position' => fn (array $attributes): int => (int) StructureVersionPage::query()
                ->where('structure_version_id', $attributes['structure_version_id'])
                ->max('position') + 1,
        ];
    }
}
