<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\SectionVersion;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $version = TemplateVersion::factory()->create();

        $this->assertTrue($version->account()->exists());
    }

    #[Test]
    public function it_belongs_to_a_template(): void
    {
        $version = TemplateVersion::factory()->create();

        $this->assertTrue($version->template()->exists());
    }

    #[Test]
    public function it_belongs_to_the_version_it_was_made_from(): void
    {
        $first = TemplateVersion::factory()->published()->create();
        $second = TemplateVersion::factory()->create([
            'template_id' => $first->template_id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $this->assertTrue($second->previousVersion()->exists());
    }

    #[Test]
    public function it_carries_its_sections_in_order(): void
    {
        $version = TemplateVersion::factory()->create();
        TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 2]);
        TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 1]);

        $this->assertEquals([1, 2], $version->assembly->pluck('position')->all());
        $this->assertCount(2, $version->sectionVersions);
    }

    #[Test]
    public function it_starts_as_an_editable_draft(): void
    {
        $version = TemplateVersion::factory()->create();

        $this->assertEquals(VersionStatusEnum::Draft, $version->status);
        $this->assertTrue($version->status->isEditable());
    }

    #[Test]
    public function it_refuses_to_change_what_it_contains_once_it_is_published(): void
    {
        $version = TemplateVersion::factory()->published()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        $version->change_note = 'Sneaking a change in';
        $version->save();
    }

    /**
     * Retiring and counting records are things that happen to a published
     * version rather than changes to what it renders, so neither is refused.
     */
    #[Test]
    public function it_still_lets_a_published_version_be_retired_and_counted(): void
    {
        $version = TemplateVersion::factory()->published()->create();

        $version->status = VersionStatusEnum::Retired;
        $version->retired_at = now();
        $version->record_count = 12;
        $version->save();

        $this->assertEquals(VersionStatusEnum::Retired, $version->fresh()->status);
        $this->assertEquals(12, $version->fresh()->record_count);
    }

    #[Test]
    public function it_refuses_to_be_deleted_once_it_is_published(): void
    {
        $version = TemplateVersion::factory()->published()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        $version->delete();
    }

    #[Test]
    public function it_refuses_to_be_deleted_while_records_point_at_it(): void
    {
        $version = TemplateVersion::factory()->used()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        $version->delete();
    }

    #[Test]
    public function it_lets_a_draft_be_deleted(): void
    {
        $version = TemplateVersion::factory()->create();

        $version->delete();

        $this->assertModelMissing($version);
    }

    #[Test]
    public function it_takes_its_account_from_the_template_it_belongs_to(): void
    {
        $version = TemplateVersion::factory()->create();

        $this->assertEquals($version->template->account_id, $version->account_id);
    }

    #[Test]
    public function it_places_a_section_by_its_version_rather_than_by_the_section_itself(): void
    {
        $sectionVersion = SectionVersion::factory()->published()->create();
        $version = TemplateVersion::factory()->create();
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->assertTrue($version->sectionVersions->first()->is($sectionVersion));
    }
}
