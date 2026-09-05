<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\SectionVersion;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateVersionSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $row = TemplateVersionSection::factory()->create();

        $this->assertTrue($row->account()->exists());
    }

    #[Test]
    public function it_belongs_to_a_template_version_and_to_an_exact_section(): void
    {
        $row = TemplateVersionSection::factory()->create();

        $this->assertTrue($row->templateVersion()->exists());
        $this->assertTrue($row->sectionVersion()->exists());
    }

    #[Test]
    public function it_takes_its_account_from_the_template_version_it_belongs_to(): void
    {
        $row = TemplateVersionSection::factory()->create();

        $this->assertEquals($row->templateVersion->account_id, $row->account_id);
    }

    #[Test]
    public function it_shows_a_title_of_its_own_only_when_the_template_version_asks_for_one(): void
    {
        $row = TemplateVersionSection::factory()->create(['title_override' => 'Where they live']);

        $this->assertEquals('Where they live', $row->title_override);
        $this->assertNotEquals('Where they live', $row->sectionVersion->section->name);
    }

    #[Test]
    public function it_starts_open_unless_the_template_version_says_otherwise(): void
    {
        $this->assertFalse(TemplateVersionSection::factory()->create()->is_collapsed_by_default);
        $this->assertTrue(TemplateVersionSection::factory()->collapsed()->create()->is_collapsed_by_default);
    }

    #[Test]
    public function it_refuses_to_be_added_to_a_published_template_version(): void
    {
        $version = TemplateVersion::factory()->published()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        TemplateVersionSection::factory()->create(['template_version_id' => $version->id]);
    }

    #[Test]
    public function it_refuses_to_be_moved_on_a_published_template_version(): void
    {
        $row = TemplateVersionSection::factory()->create();
        $row->templateVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $row->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        $row->position = 5;
        $row->save();
    }

    #[Test]
    public function it_refuses_to_be_taken_off_a_published_template_version(): void
    {
        $row = TemplateVersionSection::factory()->create();
        $row->templateVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $row->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        $row->delete();
    }

    #[Test]
    public function it_refuses_to_place_the_same_section_twice_on_one_template_version(): void
    {
        $version = TemplateVersion::factory()->create();
        $sectionVersion = SectionVersion::factory()->create();
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->expectException(QueryException::class);

        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
            'position' => 2,
        ]);
    }
}
