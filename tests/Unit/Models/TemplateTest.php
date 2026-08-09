<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\TemplateAppliesToEnum;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\TemplateVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $template = Template::factory()->create();

        $this->assertTrue($template->account()->exists());
    }

    #[Test]
    public function it_belongs_to_the_kind_of_record_it_describes(): void
    {
        $entityType = EntityType::factory()->create();
        $template = Template::factory()->forEntity($entityType)->create(['account_id' => $entityType->account_id]);

        $this->assertTrue($template->entityType()->exists());
        $this->assertEquals(TemplateAppliesToEnum::Entity, $template->applies_to);
    }

    #[Test]
    public function it_has_no_kind_of_record_when_it_describes_a_contact(): void
    {
        $template = Template::factory()->create();

        $this->assertNull($template->entity_type_id);
        $this->assertEquals(TemplateAppliesToEnum::Contact, $template->applies_to);
    }

    #[Test]
    public function it_has_many_versions(): void
    {
        $template = Template::factory()->create();
        TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 1]);

        $this->assertTrue($template->versions()->exists());
    }

    #[Test]
    public function it_lists_its_versions_newest_first(): void
    {
        $template = Template::factory()->create();
        TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 1]);
        TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 2]);

        $this->assertEquals([2, 1], $template->versions->pluck('version_number')->all());
    }

    #[Test]
    public function it_points_at_the_version_it_offers(): void
    {
        $template = Template::factory()->create();
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $template->published_version_id = $version->id;
        $template->save();

        $this->assertTrue($template->publishedVersion()->exists());
    }

    #[Test]
    public function it_offers_nothing_while_only_drafts_exist(): void
    {
        $template = Template::factory()->create();

        $this->assertNull($template->published_version_id);
    }

    #[Test]
    public function it_reads_by_its_translation_key_when_the_product_supplied_it(): void
    {
        $template = Template::factory()->supplied()->create();

        $this->assertEquals('Contact', $template->name);
        $this->assertNull($template->getRawOriginal('name'));
    }

    #[Test]
    public function it_reads_by_the_name_it_was_given(): void
    {
        $template = Template::factory()->create(['name' => 'Pet']);

        $this->assertEquals('Pet', $template->name);
    }

    #[Test]
    public function it_is_reached_by_its_slug(): void
    {
        $template = Template::factory()->make();

        $this->assertEquals('slug', $template->getRouteKeyName());
    }

    #[Test]
    public function it_is_given_an_unguessable_identifier(): void
    {
        $template = Template::factory()->create();

        $this->assertNotNull($template->uuid);
    }
}
