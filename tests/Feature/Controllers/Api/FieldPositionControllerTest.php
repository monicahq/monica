<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\VersionStatusEnum;
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

class FieldPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_field_and_renumbers_the_section_from_one(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $fields = collect(range(1, 3))->map(fn (int $position): Field => Field::factory()->create([
            'section_version_id' => $row->section_version_id,
            'position' => $position,
        ]));

        Sanctum::actingAs($user);

        $this->json('PUT', $this->url($template, $row)."/fields/{$fields[2]->id}/position", ['position' => 1])
            ->assertOk()
            ->assertJsonPath('data.attributes.position', 1);

        $this->assertSame(1, $fields[2]->refresh()->position);
        $this->assertSame(2, $fields[0]->refresh()->position);
        $this->assertSame(3, $fields[1]->refresh()->position);
    }

    #[Test]
    public function it_reorders_in_a_draft_of_a_section_that_is_published(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $published = $row->sectionVersion;
        $first = Field::factory()->create(['section_version_id' => $published->id, 'label' => 'Street', 'position' => 1]);
        Field::factory()->create(['section_version_id' => $published->id, 'label' => 'City', 'position' => 2]);
        $published->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        Sanctum::actingAs($user);

        $this->json('PUT', $this->url($template, $row)."/fields/{$first->id}/position", ['position' => 2])
            ->assertOk();

        $this->assertSame(1, $first->refresh()->position);
        $this->assertEquals(VersionStatusEnum::Published, $published->refresh()->status);

        $draft = $row->refresh()->sectionVersion;

        $this->assertEquals(VersionStatusEnum::Draft, $draft->status);
        $this->assertSame(['City', 'Street'], $draft->fields()->pluck('label')->all());
    }

    #[Test]
    public function it_refuses_to_move_a_field_on_a_published_version(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);
        Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 2]);
        $row->sectionVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $row->structureVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        Sanctum::actingAs($user);

        $this->json('PUT', $this->url($template, $row)."/fields/{$field->id}/position", ['position' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['version']);

        $this->assertSame(1, $field->refresh()->position);
    }

    #[Test]
    public function it_validates_the_position(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);

        Sanctum::actingAs($user);

        $this->json('PUT', $this->url($template, $row)."/fields/{$field->id}/position", ['position' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position']);
    }

    #[Test]
    public function it_returns_not_found_for_a_field_of_a_template_from_another_account(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        [, $template, $row] = $this->placement();
        $field = Field::factory()->create(['section_version_id' => $row->section_version_id, 'position' => 1]);

        Sanctum::actingAs($user);

        $this->json('PUT', $this->url($template, $row)."/fields/{$field->id}/position", ['position' => 1])
            ->assertNotFound();
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
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create([
                'section_id' => Section::factory()->create(['account_id' => $user->account_id])->id,
            ])->id,
        ]);

        return [$user, $template, $row];
    }
}
