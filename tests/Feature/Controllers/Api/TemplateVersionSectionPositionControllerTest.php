<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateVersionSectionPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_section_and_renumbers_the_page_from_one(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 1]);
        $rows = collect(range(1, 3))->map(fn (int $position): TemplateVersionSection => TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create([
                'section_id' => Section::factory()->create(['account_id' => $user->account_id])->id,
            ])->id,
            'position' => $position,
        ]));

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$rows[2]->id}/position", ['position' => 1])
            ->assertOk()
            ->assertJsonPath('data.attributes.position', 1);

        $this->assertSame(1, $rows[2]->refresh()->position);
        $this->assertSame(2, $rows[0]->refresh()->position);
        $this->assertSame(3, $rows[1]->refresh()->position);
    }

    #[Test]
    public function it_validates_the_position(): void
    {
        Queue::fake();
        [$user, $template, $row] = $this->placement();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/position", ['position' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position']);
    }

    #[Test]
    public function it_returns_not_found_for_a_placement_of_a_template_from_another_account(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        [, $template, $row] = $this->placement();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/templates/{$template->uuid}/versions/1/sections/{$row->id}/position", ['position' => 1])
            ->assertNotFound();

        $this->assertSame(1, $row->refresh()->position);
    }

    /** @return array{User, Template, TemplateVersionSection} */
    private function placement(): array
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 1]);
        $row = TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => SectionVersion::factory()->create([
                'section_id' => Section::factory()->create(['account_id' => $user->account_id])->id,
            ])->id,
            'position' => 1,
        ]);

        return [$user, $template, $row];
    }
}
