<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\StructureAppliesToEnum;
use App\Models\StructureVersion;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_which_kind_of_record_it_describes(): void
    {
        $contact = Template::factory()->create()->structure;
        $entity = Template::factory()->forEntity()->create()->structure;

        $this->assertEquals(StructureAppliesToEnum::Contact, $contact->applies_to);
        $this->assertEquals(StructureAppliesToEnum::Entity, $entity->applies_to);
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
