<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<StructureVersionSection> */
class StructureVersionSectionFactory extends Factory
{
    protected $model = StructureVersionSection::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'structure_version_id' => StructureVersion::factory(),
            'section_version_id' => SectionVersion::factory(),
            'account_id' => fn (array $attributes): int => StructureVersion::query()->findOrFail($attributes['structure_version_id'])->account_id,
            'structure_version_page_id' => fn (array $attributes): int => $this->firstPageOf((int) $attributes['structure_version_id']),
            'position' => 1,
            'title_override' => null,
            'shows_title' => true,
            'is_collapsed_by_default' => false,
        ];
    }

    private function firstPageOf(int $structureVersionId): int
    {
        $page = StructureVersionPage::query()
            ->where('structure_version_id', $structureVersionId)
            ->orderBy('position')
            ->value('id');

        return $page ?? StructureVersionPage::factory()->create([
            'structure_version_id' => $structureVersionId,
        ])->id;
    }

    public function collapsed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_collapsed_by_default' => true,
        ]);
    }

    public function withoutTitle(): static
    {
        return $this->state(fn (array $attributes): array => [
            'shows_title' => false,
        ]);
    }
}
