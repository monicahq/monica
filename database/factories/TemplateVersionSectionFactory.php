<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SectionVersion;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateVersionSection>
 */
class TemplateVersionSectionFactory extends Factory
{
    protected $model = TemplateVersionSection::class;

    /**
     * The account is taken from the template version the section is placed in, since an
     * assembly belonging to a different account than its template version is nothing.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_version_id' => TemplateVersion::factory(),
            'section_version_id' => SectionVersion::factory(),
            'account_id' => fn (array $attributes): int => TemplateVersion::query()->findOrFail($attributes['template_version_id'])->account_id,
            'position' => 1,
            'title_override' => null,
            'is_collapsed_by_default' => false,
        ];
    }

    /**
     * Indicate that the section starts folded, so a long page stays readable.
     */
    public function collapsed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_collapsed_by_default' => true,
        ]);
    }
}
