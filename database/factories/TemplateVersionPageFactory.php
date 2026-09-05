<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<TemplateVersionPage> */
class TemplateVersionPageFactory extends Factory
{
    protected $model = TemplateVersionPage::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        $label = Str::ucfirst(fake()->unique()->word());

        return [
            'template_version_id' => TemplateVersion::factory(),
            'account_id' => fn (array $attributes): int => TemplateVersion::query()->findOrFail($attributes['template_version_id'])->account_id,
            'key' => Str::slug($label),
            'label' => $label,
            'label_translation_key' => null,
            'position' => fn (array $attributes): int => (int) TemplateVersionPage::query()
                ->where('template_version_id', $attributes['template_version_id'])
                ->max('position') + 1,
        ];
    }
}
