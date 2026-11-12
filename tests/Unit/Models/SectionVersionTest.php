<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\Field;
use App\Models\SectionVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_owns_the_fields_in_order(): void
    {
        $version = SectionVersion::factory()->create();
        Field::factory()->create(['section_version_id' => $version->id, 'position' => 2]);
        Field::factory()->create(['section_version_id' => $version->id, 'position' => 1]);

        $this->assertEquals([1, 2], $version->fields->pluck('position')->all());
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
}
