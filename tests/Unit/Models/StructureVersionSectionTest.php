<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\LifeEventType;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $row = StructureVersionSection::factory()->create();

        $this->assertTrue($row->account()->exists());
    }

    #[Test]
    public function it_belongs_to_a_structure_version_and_to_an_exact_section(): void
    {
        $row = StructureVersionSection::factory()->create();

        $this->assertTrue($row->structureVersion()->exists());
        $this->assertTrue($row->sectionVersion()->exists());
    }

    #[Test]
    public function it_takes_its_account_from_the_structure_version_it_belongs_to(): void
    {
        $row = StructureVersionSection::factory()->create();

        $this->assertEquals($row->structureVersion->account_id, $row->account_id);
    }

    #[Test]
    public function it_shows_a_title_of_its_own_only_when_the_structure_version_asks_for_one(): void
    {
        $row = StructureVersionSection::factory()->create(['title_override' => 'Where they live']);

        $this->assertEquals('Where they live', $row->title_override);
        $this->assertNotEquals('Where they live', $row->sectionVersion->section->name);
    }

    #[Test]
    public function it_starts_open_unless_the_structure_version_says_otherwise(): void
    {
        $this->assertFalse(StructureVersionSection::factory()->create()->is_collapsed_by_default);
        $this->assertTrue(StructureVersionSection::factory()->collapsed()->create()->is_collapsed_by_default);
    }

    #[Test]
    public function it_refuses_to_be_added_to_a_published_structure_version(): void
    {
        $version = StructureVersion::factory()->published()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        StructureVersionSection::factory()->create(['structure_version_id' => $version->id]);
    }

    #[Test]
    public function it_refuses_to_be_moved_on_a_published_structure_version(): void
    {
        $row = StructureVersionSection::factory()->create();
        $row->structureVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $row->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        $row->position = 5;
        $row->save();
    }

    #[Test]
    public function it_refuses_to_be_taken_off_a_published_structure_version(): void
    {
        $row = StructureVersionSection::factory()->create();
        $row->structureVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $row->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        $row->delete();
    }

    #[Test]
    public function it_refuses_to_place_the_same_section_twice_on_one_structure_version(): void
    {
        $version = StructureVersion::factory()->create();
        $sectionVersion = SectionVersion::factory()->create();
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->expectException(QueryException::class);

        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
            'position' => 2,
        ]);
    }

    #[Test]
    public function it_is_reachable_through_the_template_or_the_life_event_type_that_owns_its_structure(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $lifeEventType = LifeEventType::factory()->create(['account_id' => $user->account_id]);
        $structure = $lifeEventType->structure;

        $onTheTemplate = StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'structure_version_id' => StructureVersion::factory()->create([
                'account_id' => $user->account_id,
                'structure_id' => $template->structure->id,
            ])->id,
        ]);

        $onTheType = StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'structure_version_id' => StructureVersion::factory()->create([
                'account_id' => $user->account_id,
                'structure_id' => $structure->id,
                'version_number' => 2,
            ])->id,
        ]);

        $this->assertTrue($onTheTemplate->isReachableThrough($user, $template));
        $this->assertTrue($onTheType->isReachableThrough($user, $lifeEventType));
        $this->assertFalse($onTheType->isReachableThrough($user, $template));
        $this->assertFalse($onTheTemplate->isReachableThrough($user, $lifeEventType));
    }
}
