<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Template;
use App\Models\User;
use Tests\TestCase;

class StructureVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $version = StructureVersion::factory()->create();

        $this->assertTrue($version->account()->exists());
    }

    #[Test]
    public function it_belongs_to_a_template(): void
    {
        $version = StructureVersion::factory()->create();

        $this->assertTrue($version->structure->owner()->exists());
    }

    #[Test]
    public function it_belongs_to_the_version_it_was_made_from(): void
    {
        $first = StructureVersion::factory()->published()->create();
        $second = StructureVersion::factory()->create([
            'structure_id' => $first->structure_id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $this->assertTrue($second->previousVersion()->exists());
    }

    #[Test]
    public function it_carries_its_sections_in_order(): void
    {
        $version = StructureVersion::factory()->create();
        StructureVersionSection::factory()->create(['structure_version_id' => $version->id, 'position' => 2]);
        StructureVersionSection::factory()->create(['structure_version_id' => $version->id, 'position' => 1]);

        $this->assertEquals([1, 2], $version->assembly->pluck('position')->all());
        $this->assertCount(2, $version->sectionVersions);
    }

    #[Test]
    public function it_starts_as_an_editable_draft(): void
    {
        $version = StructureVersion::factory()->create();

        $this->assertEquals(VersionStatusEnum::Draft, $version->status);
        $this->assertTrue($version->status->isEditable());
    }

    #[Test]
    public function it_refuses_to_change_what_it_contains_once_it_is_published(): void
    {
        $version = StructureVersion::factory()->published()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        $version->change_note = 'Sneaking a change in';
        $version->save();
    }

    #[Test]
    public function it_still_lets_a_published_version_be_retired_and_counted(): void
    {
        $version = StructureVersion::factory()->published()->create();

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
        $version = StructureVersion::factory()->published()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        $version->delete();
    }

    #[Test]
    public function it_refuses_to_be_deleted_while_records_point_at_it(): void
    {
        $version = StructureVersion::factory()->used()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        $version->delete();
    }

    #[Test]
    public function it_lets_a_draft_be_deleted(): void
    {
        $version = StructureVersion::factory()->create();

        $version->delete();

        $this->assertModelMissing($version);
    }

    #[Test]
    public function it_takes_its_account_from_the_template_it_belongs_to(): void
    {
        $version = StructureVersion::factory()->create();

        $this->assertEquals($version->structure->owner->account_id, $version->account_id);
    }

    #[Test]
    public function it_places_a_section_by_its_version_rather_than_by_the_section_itself(): void
    {
        $sectionVersion = SectionVersion::factory()->published()->create();
        $version = StructureVersion::factory()->create();
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->assertTrue($version->sectionVersions->first()->is($sectionVersion));
    }

    #[Test]
    public function it_is_reachable_through_the_template_it_belongs_to(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $ofAnotherTemplate = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $ofAnotherAccount = StructureVersion::factory()->create();

        $this->assertTrue($version->isReachableThrough($user, $template));
        $this->assertFalse($ofAnotherTemplate->isReachableThrough($user, $template));
        $this->assertFalse($ofAnotherAccount->isReachableThrough($user, $template));
    }
}
