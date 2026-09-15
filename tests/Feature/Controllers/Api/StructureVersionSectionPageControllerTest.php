<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureVersionSectionPageControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_section_to_another_page_of_the_same_version(): void
    {
        Queue::fake();
        [$user, $template, $version, $row] = $this->placement();
        $second = StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/page", [
            'page_id' => $second->uuid,
        ])->assertOk();

        $this->assertSame($second->id, $row->refresh()->structure_version_page_id);
    }

    #[Test]
    public function it_returns_not_found_for_a_page_on_another_version(): void
    {
        Queue::fake();
        [$user, $template, $version, $row] = $this->placement();
        $other = StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 2]);
        $page = $other->pages()->sole();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/page", [
            'page_id' => $page->uuid,
        ])->assertNotFound();

        $this->assertSame($version->pages()->sole()->id, $row->refresh()->structure_version_page_id);
    }

    #[Test]
    public function it_validates_that_a_page_is_named(): void
    {
        Queue::fake();
        [$user, $template, , $row] = $this->placement();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/page", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page_id']);
    }

    #[Test]
    public function it_returns_not_found_for_a_placement_of_a_template_from_another_account(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        [, $template, $version, $row] = $this->placement();
        $page = $version->pages()->sole();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/page", [
            'page_id' => $page->uuid,
        ])->assertNotFound();
    }

    /** @return array{User, Template, StructureVersion, StructureVersionSection} */
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

        return [$user, $template, $version, $row];
    }
}
