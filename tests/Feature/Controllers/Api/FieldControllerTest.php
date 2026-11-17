<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\VersionStatusEnum;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'key', 'type', 'label', 'help_text', 'placeholder', 'position',
                'is_required', 'is_sensitive', 'is_highlighted', 'is_unique',
                'is_repeatable', 'repeat_min', 'repeat_max', 'repetition_order',
                'options', 'validation', 'default_value', 'relation_target',
                'created_at', 'updated_at',
            ],
        ],
    ];

    #[Test]
    public function it_adds_a_field_to_a_section_of_a_draft(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();

        Sanctum::actingAs($user);

        $response = $this->json('POST', $this->url($template, $row).'/fields', [
            'type' => 'text',
            'label' => 'Nickname',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.label', 'Nickname');
        $response->assertJsonPath('data.attributes.type', 'text');
        $response->assertJsonPath('data.attributes.is_required', false);
        $response->assertJsonPath('data.attributes.is_repeatable', false);
    }

    #[Test]
    public function it_takes_an_email_that_names_no_list_at_all(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();

        Sanctum::actingAs($user);

        $this->json('POST', $this->url($template, $row).'/fields', [
            'type' => 'email',
            'label' => 'Email',
        ])->assertCreated();

        $this->assertDatabaseHas('fields', ['type' => 'email', 'value_list_id' => null]);
    }

    #[Test]
    public function it_configures_a_field(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', $this->url($template, $row)."/fields/{$field->id}", [
            'label' => 'Street name',
            'help_text' => 'Including the number.',
            'placeholder' => '221B Baker Street',
            'is_required' => true,
            'is_sensitive' => true,
            'is_highlighted' => true,
            'is_unique' => true,
            'validation' => ['max' => 120],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.label', 'Street name');
        $response->assertJsonPath('data.attributes.help_text', 'Including the number.');
        $response->assertJsonPath('data.attributes.is_required', true);
        $response->assertJsonPath('data.attributes.is_sensitive', true);
        $response->assertJsonPath('data.attributes.is_unique', true);
        $response->assertJsonPath('data.attributes.validation.max', 120);
    }

    #[Test]
    public function it_keeps_what_is_left_out_of_an_update(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'position' => 1,
            'label' => 'Branch',
            'help_text' => 'Where Dwight sells paper.',
            'placeholder' => 'Scranton',
            'is_required' => true,
            'is_sensitive' => true,
            'validation' => ['max' => 120],
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', $this->url($template, $row)."/fields/{$field->id}", [
            'label' => 'Office',
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.label', 'Office')
            ->assertJsonPath('data.attributes.help_text', 'Where Dwight sells paper.')
            ->assertJsonPath('data.attributes.placeholder', 'Scranton')
            ->assertJsonPath('data.attributes.is_required', true)
            ->assertJsonPath('data.attributes.is_sensitive', true)
            ->assertJsonPath('data.attributes.validation.max', 120);
    }

    #[Test]
    public function it_clears_a_field_sent_as_null(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $field = Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'position' => 1,
            'label' => 'Branch',
            'help_text' => 'Where Dwight sells paper.',
            'placeholder' => 'Scranton',
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', $this->url($template, $row)."/fields/{$field->id}", [
            'help_text' => null,
            'placeholder' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.label', 'Branch')
            ->assertJsonPath('data.attributes.help_text', null)
            ->assertJsonPath('data.attributes.placeholder', null);
    }

    #[Test]
    public function it_refuses_a_label_sent_as_null(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1, 'label' => 'Branch']);

        Sanctum::actingAs($user);

        $this->json('PUT', $this->url($template, $row)."/fields/{$field->id}", ['label' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['label']);

        $this->assertSame('Branch', $field->refresh()->label);
    }

    #[Test]
    public function it_refuses_a_relation_to_a_kind_of_record_that_is_turned_off(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $animals = EntityType::factory()->create(['account_id' => $user->account_id, 'is_active' => false]);

        Sanctum::actingAs($user);

        $this->json('POST', $this->url($template, $row).'/fields', [
            'type' => 'relation',
            'label' => 'Mother',
            'relation_target' => 'entity',
            'relation_entity_type_id' => $animals->uuid,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('relation_entity_type_id');
    }

    #[Test]
    public function it_opens_a_draft_of_a_published_section_and_leaves_the_published_one_alone(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $published = $row->sectionVersion;
        $field = Field::factory()->create(['section_version_id' => $published->id, 'label' => 'Street', 'position' => 1]);
        $published->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        Sanctum::actingAs($user);

        $this->json('PUT', $this->url($template, $row)."/fields/{$field->id}", [
            'label' => 'Street name',
            'help_text' => null,
            'placeholder' => null,
        ])->assertOk();

        $this->assertSame('Street', $field->refresh()->label);
        $this->assertEquals(VersionStatusEnum::Published, $published->refresh()->status);

        $draft = $row->refresh()->sectionVersion;

        $this->assertNotSame($published->id, $draft->id);
        $this->assertEquals(VersionStatusEnum::Draft, $draft->status);
        $this->assertSame('Street name', $draft->fields()->sole()->label);
    }

    #[Test]
    public function it_refuses_to_add_a_field_to_a_published_version(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $row->sectionVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $row->structureVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        Sanctum::actingAs($user);

        $this->json('POST', $this->url($template, $row).'/fields', ['type' => 'text', 'label' => 'Nickname'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['version']);

        $this->assertDatabaseMissing('fields', ['label' => 'Nickname']);
    }

    #[Test]
    public function it_removes_a_field(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);

        Sanctum::actingAs($user);

        $this->json('DELETE', $this->url($template, $row)."/fields/{$field->id}")->assertNoContent();

        $this->assertModelMissing($field);
    }

    #[Test]
    public function it_returns_not_found_for_a_field_of_a_template_from_another_account(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        [, $template, $row] = $this->placement();
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);

        Sanctum::actingAs($user);

        $this->json('POST', $this->url($template, $row).'/fields', ['type' => 'text', 'label' => 'x'])->assertNotFound();
        $this->json('PUT', $this->url($template, $row)."/fields/{$field->id}", ['label' => 'x', 'help_text' => null, 'placeholder' => null])->assertNotFound();
        $this->json('DELETE', $this->url($template, $row)."/fields/{$field->id}")->assertNotFound();

        $this->assertModelExists($field);
    }

    private function url(Template $template, StructureVersionSection $row): string
    {
        return "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}";
    }

    /** @return array{User, Template, StructureVersionSection} */
    private function placement(): array
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        return [$user, $template, $row->refresh()];
    }
}
