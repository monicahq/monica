<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\LifeEventType;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_its_account_from_the_structure_version_it_belongs_to(): void
    {
        $row = StructureVersionSection::factory()->create();

        $this->assertEquals($row->structureVersion->account_id, $row->account_id);
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
