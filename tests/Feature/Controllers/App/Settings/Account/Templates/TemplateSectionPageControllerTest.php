<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateSectionPageControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_section_to_another_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $general = $version->pages()->firstOrFail();
        $events = StructureVersionPage::factory()->create(['structure_version_id' => $version->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $general->id,
        ]);

        $response = $this->actingAs($user)->put(route('settings.templateSectionPage.update', [$template, $row]), [
            'page' => $events->uuid,
        ]);

        $response->assertRedirect(route('settings.templateBuilder.index', ['template' => $template, 'page' => $events->key]));
        $response->assertSessionHas('status');
        $this->assertEquals($events->id, $row->fresh()->structure_version_page_id);
    }

    #[Test]
    public function it_refuses_a_page_on_another_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $row = StructureVersionSection::factory()->create(['structure_version_id' => $version->id]);
        $elsewhere = StructureVersion::factory()->create(['account_id' => $user->account_id])->pages()->sole();

        $this->actingAs($user)->put(route('settings.templateSectionPage.update', [$template, $row]), [
            'page' => $elsewhere->uuid,
        ])->assertSessionHasErrors('page');

        $this->assertNotEquals($elsewhere->id, $row->fresh()->structure_version_page_id);
    }
}
