<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
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
    public function it_places_a_new_field_last_in_its_section(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);

        Sanctum::actingAs($user);

        $this->json('POST', $this->url($template, $row).'/fields', ['type' => 'text', 'label' => 'Nickname'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.position', 2);
    }

    #[Test]
    public function it_settles_repeatability_from_the_type(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();

        Sanctum::actingAs($user);

        $this->json('POST', $this->url($template, $row).'/fields', ['type' => 'repeater', 'label' => 'Addresses'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.is_repeatable', true);
    }

    #[Test]
    public function it_refuses_a_field_offering_choices_with_no_list_to_take_them_from(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();

        Sanctum::actingAs($user);

        $this->json('POST', $this->url($template, $row).'/fields', ['type' => 'select', 'label' => 'Country'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function it_takes_the_list_a_field_offers_its_choices_from(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', $this->url($template, $row).'/fields', [
            'type' => 'select',
            'label' => 'Country',
            'value_list_id' => $list->id,
        ])->assertCreated();
    }

    #[Test]
    public function it_takes_the_list_a_phone_takes_its_labels_from(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', $this->url($template, $row).'/fields', [
            'type' => 'phone',
            'label' => 'Phone',
            'value_list_id' => $list->id,
        ])->assertCreated();

        $this->assertDatabaseHas('fields', ['type' => 'phone', 'value_list_id' => $list->id]);
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
    public function it_adds_a_field_of_every_type_the_product_offers(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        foreach (FieldTypeEnum::cases() as $type) {
            $payload = ['type' => $type->value, 'label' => 'A '.$type->value];

            if (in_array($type, [FieldTypeEnum::Select, FieldTypeEnum::Multiselect, FieldTypeEnum::Radio], true)) {
                $payload['value_list_id'] = $list->id;
            }

            if ($type === FieldTypeEnum::Relation) {
                $payload['relation_target'] = 'contact';
            }

            $this->json('POST', $this->url($template, $row).'/fields', $payload)
                ->assertCreated()
                ->assertJsonPath('data.attributes.type', $type->value);
        }
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
