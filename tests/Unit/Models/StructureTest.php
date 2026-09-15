<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\StructureAppliesToEnum;
use App\Models\EntityType;
use App\Models\StructureVersion;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $structure = Template::factory()->create()->structure;

        $this->assertTrue($structure->account()->exists());
    }

    #[Test]
    public function it_names_the_thing_that_owns_it(): void
    {
        $template = Template::factory()->create();

        $this->assertTrue($template->structure->owner->is($template));
    }

    #[Test]
    public function it_says_which_kind_of_record_it_describes(): void
    {
        $contact = Template::factory()->create()->structure;
        $entity = Template::factory()->forEntity()->create()->structure;

        $this->assertEquals(StructureAppliesToEnum::Contact, $contact->applies_to);
        $this->assertEquals(StructureAppliesToEnum::Entity, $entity->applies_to);
    }

    #[Test]
    public function it_belongs_to_the_kind_of_record_only_when_it_describes_an_entity(): void
    {
        $contact = Template::factory()->create()->structure;
        $entity = Template::factory()->forEntity()->create()->structure;

        $this->assertNull($contact->entity_type_id);
        $this->assertInstanceOf(EntityType::class, $entity->entityType);
    }

    #[Test]
    public function it_has_many_versions(): void
    {
        $structure = Template::factory()->create()->structure;
        StructureVersion::factory()->create(['structure_id' => $structure->id, 'version_number' => 1]);

        $this->assertTrue($structure->versions()->exists());
    }

    #[Test]
    public function it_lists_its_versions_newest_first(): void
    {
        $structure = Template::factory()->create()->structure;
        StructureVersion::factory()->create(['structure_id' => $structure->id, 'version_number' => 1]);
        StructureVersion::factory()->create(['structure_id' => $structure->id, 'version_number' => 2]);

        $this->assertEquals([2, 1], $structure->versions->pluck('version_number')->all());
    }

    #[Test]
    public function it_offers_no_version_while_only_drafts_exist(): void
    {
        $structure = Template::factory()->create()->structure;

        $this->assertNull($structure->published_version_id);
        $this->assertNull($structure->publishedVersion);
    }

    #[Test]
    public function it_points_at_the_version_a_new_record_is_given(): void
    {
        $structure = Template::factory()->create()->structure;
        $version = StructureVersion::factory()->published()->create(['structure_id' => $structure->id]);

        $structure->update(['published_version_id' => $version->id]);

        $this->assertEquals($version->id, $structure->publishedVersion->id);
    }

    #[Test]
    public function it_reads_an_owner_that_is_no_longer_there_as_nothing(): void
    {
        $template = Template::factory()->create();
        $structure = $template->structure;

        $template->forceDelete();

        $this->assertNull($structure->refresh()->owner);
    }

    #[Test]
    public function it_is_addressed_by_an_unguessable_identifier(): void
    {
        $structure = Template::factory()->create()->structure;

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $structure->uuid);
    }
}
