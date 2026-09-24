<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Section;
use App\Models\SectionVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_its_versions_newest_first(): void
    {
        $section = Section::factory()->create();
        SectionVersion::factory()->create(['section_id' => $section->id, 'version_number' => 1]);
        SectionVersion::factory()->create(['section_id' => $section->id, 'version_number' => 2]);

        $this->assertEquals([2, 1], $section->versions->pluck('version_number')->all());
    }

    #[Test]
    public function it_reads_by_its_translation_key_when_the_product_supplied_it(): void
    {
        $section = Section::factory()->supplied()->create();

        $this->assertEquals('Address', $section->name);
        $this->assertNull($section->getRawOriginal('name'));
    }

    #[Test]
    public function it_reads_by_the_name_it_was_given(): void
    {
        $section = Section::factory()->create(['name' => 'Medical follow up']);

        $this->assertEquals('Medical follow up', $section->name);
    }

    #[Test]
    public function it_is_reached_by_its_slug(): void
    {
        $section = Section::factory()->make();

        $this->assertEquals('slug', $section->getRouteKeyName());
    }

    #[Test]
    public function it_is_given_an_unguessable_identifier(): void
    {
        $section = Section::factory()->create();

        $this->assertNotNull($section->uuid);
    }

    #[Test]
    public function it_offers_thirty_seven_glyphs(): void
    {
        $this->assertCount(37, Section::ICONS);
        $this->assertSame(array_values(array_unique(Section::ICONS)), Section::ICONS);
    }

    #[Test]
    public function it_offers_no_glyph_the_icon_component_cannot_draw(): void
    {
        $drawn = [];

        foreach (Section::ICONS as $icon) {
            $drawn[$icon] = $this->blade('<x-icon :name="$icon" />', ['icon' => $icon])->__toString();
        }

        $duplicates = array_keys(array_diff_key($drawn, array_unique($drawn)));

        $this->assertSame([], $duplicates, implode(', ', $duplicates).' offered to a section but not drawn.');
        $this->assertCount(37, array_unique($drawn));
    }

    #[Test]
    public function it_defaults_to_a_glyph_it_offers(): void
    {
        $this->assertContains(Section::DEFAULT_ICON, Section::ICONS);
    }
}
