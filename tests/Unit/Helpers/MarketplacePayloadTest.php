<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Actions\CreateLifeEventType;
use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\ListingHasNothingToPublish;
use App\Helpers\MarketplacePayload;
use App\Helpers\ValueListSnapshot;
use App\Models\Field;
use App\Models\LifeEventCategory;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplacePayloadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_down_a_section_published_on_its_own(): void
    {
        $section = Section::factory()->create(['name' => 'Medical follow up', 'icon' => 'heart']);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create([
            'account_id' => $section->account_id,
            'section_version_id' => $version->id,
            'key' => 'diagnosis',
            'label' => 'Diagnosis',
            'position' => 1,
        ]);
        $this->publish($section, $version);

        $payload = MarketplacePayload::of($section);

        $this->assertEquals('section', $payload['type']);
        $this->assertEquals('Medical follow up', $payload['section']['name']);
        $this->assertEquals('heart', $payload['section']['icon']);
        $this->assertCount(1, $payload['section']['fields']);
        $this->assertEquals('diagnosis', $payload['section']['fields'][0]['key']);
        $this->assertEquals(FieldTypeEnum::Text->value, $payload['section']['fields'][0]['type']);
    }

    #[Test]
    public function it_writes_down_the_mark_a_section_carries(): void
    {
        $section = Section::factory()->marked('health', '#4f8a49')->create(['name' => 'Medical follow up']);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create([
            'account_id' => $section->account_id,
            'section_version_id' => $version->id,
            'key' => 'diagnosis',
            'label' => 'Diagnosis',
            'position' => 1,
        ]);
        $this->publish($section, $version);

        $payload = MarketplacePayload::of($section);

        $this->assertEquals('health', $payload['section']['icon']);
        $this->assertEquals('#4f8a49', $payload['section']['colour']);
    }

    #[Test]
    public function it_writes_down_an_empty_mark_for_a_section_carrying_none(): void
    {
        $section = Section::factory()->create(['name' => 'Medical follow up']);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create([
            'account_id' => $section->account_id,
            'section_version_id' => $version->id,
            'key' => 'diagnosis',
            'label' => 'Diagnosis',
            'position' => 1,
        ]);
        $this->publish($section, $version);

        $payload = MarketplacePayload::of($section);

        $this->assertNull($payload['section']['icon']);
        $this->assertNull($payload['section']['colour']);
    }

    #[Test]
    public function it_writes_down_a_template_with_every_page_and_what_sits_on_it(): void
    {
        $template = Template::factory()->create(['name' => 'Patient']);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $second = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'label' => 'Treatments',
        ]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $version->pages()->first()->id,
            'position' => 1,
        ]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $second->id,
            'position' => 1,
            'title_override' => 'What was prescribed',
        ]);
        $this->publish($template, $version);

        $payload = MarketplacePayload::of($template);

        $this->assertEquals('template', $payload['type']);
        $this->assertEquals('Patient', $payload['template']['name']);
        $this->assertCount(2, $payload['template']['pages']);
        $this->assertEquals('Treatments', $payload['template']['pages'][1]['label']);
        $this->assertEquals('What was prescribed', $payload['template']['pages'][1]['sections'][0]['title_override']);
        $this->assertArrayHasKey('fields', $payload['template']['pages'][1]['sections'][0]['section']);
    }

    #[Test]
    public function it_carries_the_choices_a_field_reads_from(): void
    {
        $section = Section::factory()->create();
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        $list = ValueList::query()->create([
            'account_id' => $section->account_id,
            'key' => 'departments',
            'name' => 'Departments',
            'name_translation_key' => null,
            'description' => null,
            'is_system' => false,
            'allows_user_values' => true,
            'position' => 1,
        ]);
        ValueListItem::query()->create([
            'account_id' => $section->account_id,
            'value_list_id' => $list->id,
            'key' => 'sales',
            'label' => 'Sales',
            'label_translation_key' => null,
            'position' => 1,
            'is_system' => false,
            'is_active' => true,
            'usage_count' => 0,
        ]);
        Field::factory()->create([
            'account_id' => $section->account_id,
            'section_version_id' => $version->id,
            'value_list_id' => $list->id,
        ]);
        $version->resolved_value_lists = ValueListSnapshot::of($version);
        $this->publish($section, $version);

        $payload = MarketplacePayload::of($section);

        $this->assertEquals('departments', $payload['section']['fields'][0]['value_list_reference']);
        $this->assertEquals('Departments', $payload['value_lists']['departments']['name']);
        $this->assertEquals('Sales', $payload['value_lists']['departments']['items'][0]['label']);
    }

    #[Test]
    public function it_names_the_parent_of_a_nested_field_by_key(): void
    {
        $section = Section::factory()->create();
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        $parent = Field::factory()->create([
            'account_id' => $section->account_id,
            'section_version_id' => $version->id,
            'key' => 'medication',
            'position' => 1,
            'is_repeatable' => true,
        ]);
        Field::factory()->create([
            'account_id' => $section->account_id,
            'section_version_id' => $version->id,
            'key' => 'dose',
            'position' => 2,
            'parent_field_id' => $parent->id,
        ]);
        $this->publish($section, $version);

        $payload = MarketplacePayload::of($section);

        $this->assertNull($payload['section']['fields'][0]['parent_field_key']);
        $this->assertEquals('medication', $payload['section']['fields'][1]['parent_field_key']);
        $this->assertArrayNotHasKey('parent_field_id', $payload['section']['fields'][1]);
    }

    #[Test]
    public function it_refuses_to_write_down_something_nobody_has_published(): void
    {
        $section = Section::factory()->create();

        $this->expectException(ListingHasNothingToPublish::class);

        MarketplacePayload::of($section);
    }

    #[Test]
    public function it_hashes_the_same_payload_the_same_way_every_time(): void
    {
        $payload = ['type' => 'section', 'section' => ['slug' => 'medical']];

        $this->assertEquals(MarketplacePayload::checksum($payload), MarketplacePayload::checksum($payload));
    }

    #[Test]
    public function it_hashes_a_changed_payload_differently(): void
    {
        $before = ['type' => 'section', 'section' => ['slug' => 'medical']];
        $after = ['type' => 'section', 'section' => ['slug' => 'dental']];

        $this->assertNotEquals(MarketplacePayload::checksum($before), MarketplacePayload::checksum($after));
    }

    #[Test]
    public function it_writes_down_a_life_event_type_with_the_category_it_was_filed_under(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Leisure and culture', 'key' => 'leisure']);
        $lifeEventType = new CreateLifeEventType(user: $user, lifeEventCategory: $category, name: 'Restaurant', icon: 'heart', colour: '#4f8a49')->execute();

        $payload = MarketplacePayload::of($lifeEventType);

        $this->assertEquals('life_event_type', $payload['type']);
        $this->assertEquals('Restaurant', $payload['life_event_type']['name']);
        $this->assertEquals('heart', $payload['life_event_type']['icon']);
        $this->assertEquals('#4f8a49', $payload['life_event_type']['colour']);
        $this->assertEquals('Leisure and culture', $payload['life_event_type']['category']);
        $this->assertEquals('leisure', $payload['life_event_type']['category_key']);
        $this->assertEquals(1, $payload['life_event_type']['version_number']);
        $this->assertCount(1, $payload['life_event_type']['pages']);
    }

    private function publish(Template|Section $listable, StructureVersion|SectionVersion $version): void
    {
        $version->status = VersionStatusEnum::Published;
        $version->published_at = now();
        $version->save();

        $published = $listable instanceof Template ? $listable->structure : $listable;
        $published->update(['published_version_id' => $version->id]);
    }
}
