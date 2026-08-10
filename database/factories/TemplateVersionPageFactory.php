<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TemplateVersionPage>
 */
class TemplateVersionPageFactory extends Factory
{
    protected $model = TemplateVersionPage::class;

    /**
     * The account is taken from the template version the page is part of, since
     * a page belonging to a different account than its version is nothing.
     *
     * A version arrives from its own factory already carrying the page the
     * product supplies, so a page built here is another one beside it: it takes
     * a label of its own and the next place in the row of tabs.
     *
     * @return array<string, mixed>
     */
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
