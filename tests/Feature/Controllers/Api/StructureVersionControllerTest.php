<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Account;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'version_number',
                'status',
                'change_note',
                'published_at',
                'retired_at',
                'record_count',
                'previous_version_number',
                'created_at',
                'updated_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_versions_of_a_template_newest_first(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => 2]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 3]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/templates/{$template->uuid}/versions");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.attributes.version_number', 3);
        $response->assertJsonPath('data.0.attributes.status', 'draft');
        $response->assertJsonPath('data.1.attributes.status', 'published');
        $response->assertJsonPath('data.2.attributes.status', 'published');
    }

    #[Test]
    public function it_carries_what_a_version_replaced_and_how_many_records_sit_on_it(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        StructureVersion::factory()->published()->used(7)->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
            'change_note' => 'Added the address section.',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/templates/{$template->uuid}/versions");

        $response->assertJsonPath('data.0.attributes.previous_version_number', 1);
        $response->assertJsonPath('data.0.attributes.record_count', 7);
        $response->assertJsonPath('data.0.attributes.change_note', 'Added the address section.');
        $response->assertJsonPath('data.1.attributes.previous_version_number', null);
    }

    #[Test]
    public function it_does_not_list_the_versions_of_a_template_from_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => Account::factory()->create()->id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/templates/{$template->uuid}/versions")->assertNotFound();
    }

    #[Test]
    public function it_shows_a_version_by_its_number_within_its_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/templates/{$template->uuid}/versions/2");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.version_number', 2);
        $response->assertJsonPath('data.attributes.status', 'published');
    }

    #[Test]
    public function it_shows_the_pages_the_sections_and_the_fields_a_version_carries_in_order(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        $second = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'label' => 'Medical',
            'position' => 2,
        ]);
        $first = $version->pages()->where('position', 1)->firstOrFail();

        $sectionVersion = SectionVersion::factory()->create([
            'section_id' => Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address'])->id,
        ]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $first->id,
            'section_version_id' => $sectionVersion->id,
            'position' => 1,
            'title_override' => 'Where they live',
            'is_collapsed_by_default' => true,
        ]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $second->id,
            'section_version_id' => SectionVersion::factory()->create([
                'section_id' => Section::factory()->create(['account_id' => $user->account_id])->id,
            ])->id,
            'position' => 1,
        ]);

        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Street', 'position' => 1]);
        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'label' => 'Country',
            'type' => FieldTypeEnum::Select,
            'position' => 2,
            'is_required' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/templates/{$template->uuid}/versions/1");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'attributes' => [
                    'pages' => [[
                        'type',
                        'id',
                        'attributes' => ['key', 'label', 'position', 'sections'],
                    ]],
                ],
            ],
        ]);
        $response->assertJsonCount(2, 'data.attributes.pages');
        $response->assertJsonPath('data.attributes.pages.0.id', $first->uuid);
        $response->assertJsonPath('data.attributes.pages.1.attributes.label', 'Medical');
        $response->assertJsonCount(1, 'data.attributes.pages.0.attributes.sections');
        $response->assertJsonPath('data.attributes.pages.0.attributes.sections.0.attributes.title', 'Where they live');
        $response->assertJsonPath('data.attributes.pages.0.attributes.sections.0.attributes.is_collapsed_by_default', true);
        $response->assertJsonPath('data.attributes.pages.0.attributes.sections.0.attributes.section_name', 'Address');
        $response->assertJsonCount(2, 'data.attributes.pages.0.attributes.sections.0.attributes.fields');
        $response->assertJsonPath('data.attributes.pages.0.attributes.sections.0.attributes.fields.0.attributes.label', 'Street');
        $response->assertJsonPath('data.attributes.pages.0.attributes.sections.0.attributes.fields.1.attributes.type', 'select');
        $response->assertJsonPath('data.attributes.pages.0.attributes.sections.0.attributes.fields.1.attributes.is_required', true);
    }

    #[Test]
    public function it_reads_a_version_and_everything_it_carries_in_a_bounded_number_of_queries(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        $page = $version->pages()->firstOrFail();

        foreach (range(1, 4) as $position) {
            $sectionVersion = SectionVersion::factory()->create([
                'section_id' => Section::factory()->create(['account_id' => $user->account_id])->id,
            ]);
            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position,
            ]);
            Field::factory()->count(3)->sequence(fn ($sequence): array => ['position' => $sequence->index + 1])
                ->create(['section_version_id' => $sectionVersion->id]);
        }

        Sanctum::actingAs($user);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->json('GET', "/api/v1/templates/{$template->uuid}/versions/1")->assertOk();

        $this->assertLessThan(12, $queries);
    }

    #[Test]
    public function it_returns_not_found_for_a_version_number_the_template_does_not_have(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/templates/{$template->uuid}/versions/9")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_version_of_a_template_from_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => Account::factory()->create()->id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/templates/{$template->uuid}/versions/1")->assertNotFound();
    }

    #[Test]
    public function it_opens_a_draft_from_the_published_version(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 1,
        ]);
        $template->structure->update(['published_version_id' => $published->id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/templates/{$template->uuid}/versions");

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.version_number', 2);
        $response->assertJsonPath('data.attributes.status', 'draft');
        $response->assertJsonPath('data.attributes.previous_version_number', 1);
    }

    #[Test]
    public function it_answers_with_the_draft_that_is_already_open_rather_than_starting_a_second(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 1,
        ]);
        $template->structure->update(['published_version_id' => $published->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/templates/{$template->uuid}/versions")->assertCreated();

        $this->json('POST', "/api/v1/templates/{$template->uuid}/versions")
            ->assertOk()
            ->assertJsonPath('data.attributes.version_number', 2);

        $this->assertEquals(2, $template->versions()->count());
    }

    #[Test]
    public function it_returns_not_found_when_opening_a_draft_of_a_template_with_no_version(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/templates/{$template->uuid}/versions")->assertNotFound();
    }

    #[Test]
    public function it_publishes_a_draft(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/publication", [
            'change_note' => 'The first one.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.status', 'published');
        $this->assertEquals($version->id, $template->structure->refresh()->published_version_id);
    }

    #[Test]
    public function it_refuses_to_publish_a_version_with_no_page_at_all(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        $version->pages()->delete();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/publication", ['change_note' => 'Nothing.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['version']);

        $this->assertEquals(VersionStatusEnum::Draft, $version->refresh()->status);
    }

    #[Test]
    public function it_deletes_a_draft(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/templates/{$template->uuid}/versions/1")->assertNoContent();

        $this->assertModelMissing($version);
    }

    #[Test]
    public function it_refuses_to_delete_a_version_that_is_published(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/templates/{$template->uuid}/versions/1")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['version']);

        $this->assertModelExists($version);
    }

    #[Test]
    public function it_returns_not_found_writing_to_a_version_of_a_template_from_another_account(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => Account::factory()->create()->id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/templates/{$template->uuid}/versions")->assertNotFound();
        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/publication", ['change_note' => 'x'])->assertNotFound();
        $this->json('DELETE', "/api/v1/templates/{$template->uuid}/versions/1")->assertNotFound();

        $this->assertModelExists($version);
        $this->assertEquals(VersionStatusEnum::Draft, $version->refresh()->status);
    }
}
