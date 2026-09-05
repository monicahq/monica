<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SectionVersion;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<TemplateVersionSection> */
class TemplateVersionSectionFactory extends Factory
{
    protected $model = TemplateVersionSection::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'template_version_id' => TemplateVersion::factory(),
            'section_version_id' => SectionVersion::factory(),
            'account_id' => fn (array $attributes): int => TemplateVersion::query()->findOrFail($attributes['template_version_id'])->account_id,
            'template_version_page_id' => fn (array $attributes): int => $this->firstPageOf((int) $attributes['template_version_id']),
            'position' => 1,
            'title_override' => null,
            'is_collapsed_by_default' => false,
        ];
    }

    private function firstPageOf(int $templateVersionId): int
    {
        $page = TemplateVersionPage::query()
            ->where('template_version_id', $templateVersionId)
            ->orderBy('position')
            ->value('id');

        return $page ?? TemplateVersionPage::factory()->create([
            'template_version_id' => $templateVersionId,
        ])->id;
    }

    public function collapsed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_collapsed_by_default' => true,
        ]);
    }
}
