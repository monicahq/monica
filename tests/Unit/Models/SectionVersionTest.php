<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\StructureVersionSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $version = SectionVersion::factory()->create();

        $this->assertTrue($version->account()->exists());
    }

    #[Test]
    public function it_belongs_to_a_section(): void
    {
        $version = SectionVersion::factory()->create();

        $this->assertTrue($version->section()->exists());
    }

    #[Test]
    public function it_belongs_to_the_version_it_was_made_from(): void
    {
        $first = SectionVersion::factory()->published()->create();
        $second = SectionVersion::factory()->create([
            'section_id' => $first->section_id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $this->assertTrue($second->previousVersion()->exists());
    }

    #[Test]
    public function it_owns_the_fields_in_order(): void
    {
        $version = SectionVersion::factory()->create();
        Field::factory()->create(['section_version_id' => $version->id, 'position' => 2]);
        Field::factory()->create(['section_version_id' => $version->id, 'position' => 1]);

        $this->assertEquals([1, 2], $version->fields->pluck('position')->all());
    }

    #[Test]
    public function it_knows_the_pages_it_sits_on(): void
    {
        $version = SectionVersion::factory()->create();
        StructureVersionSection::factory()->create(['section_version_id' => $version->id]);

        $this->assertTrue($version->structureVersions()->exists());
    }

    #[Test]
    public function it_starts_as_an_editable_draft(): void
    {
        $version = SectionVersion::factory()->create();

        $this->assertEquals(VersionStatusEnum::Draft, $version->status);
        $this->assertTrue($version->status->isEditable());
    }

    #[Test]
    public function it_refuses_to_change_what_it_contains_once_it_is_published(): void
    {
        $version = SectionVersion::factory()->published()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        $version->resolved_value_lists = ['tampered' => true];
        $version->save();
    }

    #[Test]
    public function it_still_lets_a_published_version_be_retired_and_counted(): void
    {
        $version = SectionVersion::factory()->published()->create();

        $version->status = VersionStatusEnum::Retired;
        $version->retired_at = now();
        $version->usage_count = 4;
        $version->save();

        $this->assertEquals(VersionStatusEnum::Retired, $version->fresh()->status);
        $this->assertEquals(4, $version->fresh()->usage_count);
    }

    #[Test]
    public function it_refuses_to_be_deleted_once_it_is_published(): void
    {
        $version = SectionVersion::factory()->published()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        $version->delete();
    }

    #[Test]
    public function it_refuses_to_be_deleted_while_a_structure_version_carries_it(): void
    {
        $version = SectionVersion::factory()->used()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        $version->delete();
    }

    #[Test]
    public function it_takes_its_fields_with_it_when_a_draft_is_deleted(): void
    {
        $version = SectionVersion::factory()->create();
        $field = Field::factory()->create(['section_version_id' => $version->id]);

        $version->delete();

        $this->assertModelMissing($version);
        $this->assertModelMissing($field);
    }

    #[Test]
    public function it_takes_its_account_from_the_section_it_belongs_to(): void
    {
        $version = SectionVersion::factory()->create();

        $this->assertEquals($version->section->account_id, $version->account_id);
    }

    #[Test]
    public function it_reads_its_snapshot_of_the_value_lists_back_as_an_array(): void
    {
        $version = SectionVersion::factory()->create([
            'resolved_value_lists' => [7 => ['id' => 7, 'key' => 'genders', 'items' => []]],
        ]);

        $this->assertEquals('genders', $version->fresh()->resolved_value_lists[7]['key']);
    }
}
