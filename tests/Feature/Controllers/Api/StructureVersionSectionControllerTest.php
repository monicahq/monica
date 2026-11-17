<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\VaultColourEnum;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionSectionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'position',
                'title',
                'title_override',
                'section_id',
                'section_name',
                'section_version_number',
                'section_version_status',
                'is_collapsed_by_default',
                'fields',
            ],
        ],
    ];

    #[Test]
    public function it_places_a_section_on_a_draft(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $section->update(['published_version_id' => $sectionVersion->id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/templates/{$template->uuid}/versions/1/sections", [
            'section_id' => $section->uuid,
            'title_override' => 'Where they live',
            'is_collapsed_by_default' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.section_id', $section->uuid);
        $response->assertJsonPath('data.attributes.section_name', 'Address');
        $response->assertJsonPath('data.attributes.title', 'Where they live');
        $response->assertJsonPath('data.attributes.is_collapsed_by_default', true);
        $response->assertJsonPath('data.attributes.section_version_number', 1);
    }

    #[Test]
    public function it_creates_a_new_section_in_a_draft(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/templates/{$template->uuid}/versions/1/sections", [
            'name' => 'Sales',
            'icon' => 'briefcase',
            'colour' => VaultColourEnum::Green->value,
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.section_name', 'Sales');

        $this->assertDatabaseHas('sections', [
            'account_id' => $user->account_id,
            'name' => 'Sales',
            'icon' => 'briefcase',
            'colour' => VaultColourEnum::Green->value,
        ]);
        $this->assertSame('Sales', $version->assembly()->sole()->sectionVersion->section->name);
    }

    #[Test]
    public function it_refuses_a_new_section_on_a_published_version_and_writes_nothing(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/templates/{$template->uuid}/versions/1/sections", ['name' => 'Sales'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['version']);

        $this->assertDatabaseMissing('sections', ['name' => 'Sales']);
    }

    #[Test]
    public function it_refuses_a_name_and_a_section_id_together(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        $section = Section::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/templates/{$template->uuid}/versions/1/sections", [
            'name' => 'Sales',
            'section_id' => $section->uuid,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['section_id']);

        $this->assertDatabaseMissing('sections', ['name' => 'Sales']);
    }

    #[Test]
    public function it_places_a_section_on_the_page_that_was_named(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        $second = StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'position' => 2]);
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $section->update(['published_version_id' => $sectionVersion->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/templates/{$template->uuid}/versions/1/sections", [
            'section_id' => $section->uuid,
            'page_id' => $second->uuid,
        ])->assertCreated();

        $this->assertSame($second->id, $version->assembly()->sole()->structure_version_page_id);
    }

    #[Test]
    public function it_changes_the_title_a_placement_shows_and_whether_it_starts_collapsed(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}", [
            'title_override' => 'Home address',
            'is_collapsed_by_default' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.title', 'Home address')
            ->assertJsonPath('data.attributes.is_collapsed_by_default', true);
    }

    #[Test]
    public function it_keeps_what_is_left_out_of_an_update(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement(titleOverride: 'Scranton branch');
        $row->update(['is_collapsed_by_default' => true]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}", [])
            ->assertOk()
            ->assertJsonPath('data.attributes.title', 'Scranton branch')
            ->assertJsonPath('data.attributes.is_collapsed_by_default', true);
    }

    #[Test]
    public function it_clears_a_field_sent_as_null(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement(titleOverride: 'Scranton branch');

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}", [
            'title_override' => null,
        ])->assertOk();

        $this->assertNull($row->refresh()->title_override);
    }

    #[Test]
    public function it_takes_a_section_off_a_version(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}")
            ->assertNoContent();

        $this->assertModelMissing($row);
    }

    #[Test]
    public function it_returns_not_found_for_a_placement_of_a_template_from_another_account(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        [, $template, $row] = $this->placement();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}", ['title_override' => 'x'])->assertNotFound();
        $this->json('DELETE', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}")->assertNotFound();

        $this->assertModelExists($row);
    }

    /** @return array{User, Template, StructureVersionSection} */
    private function placement(?string $titleOverride = null): array
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 1]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
            'title_override' => $titleOverride,
        ]);

        return [$user, $template, $row];
    }
}
