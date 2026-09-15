<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplatePagePositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_page_to_the_position_asked_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $general = $version->pages()->firstOrFail();
        $events = StructureVersionPage::factory()->create(['structure_version_id' => $version->id]);

        $response = $this->actingAs($user)->put(route('settings.templatePagePosition.update', [$template, $events]), [
            'position' => 1,
        ]);

        $response->assertRedirect(route('settings.templateBuilder.index', ['template' => $template, 'page' => $events->key]));
        $this->assertEquals(1, $events->fresh()->position);
        $this->assertEquals(2, $general->fresh()->position);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $page = $version->pages()->sole();

        $this->actingAs($user)->put(route('settings.templatePagePosition.update', [$template, $page]), ['position' => 0])
            ->assertSessionHasErrors('position');
    }

    #[Test]
    public function it_refuses_to_reorder_the_pages_of_a_published_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $page = $version->pages()->sole();

        $this->actingAs($user)->put(route('settings.templatePagePosition.update', [$template, $page]), ['position' => 1])
            ->assertSessionHas('error');
    }

    #[Test]
    public function it_does_not_find_a_page_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $theirs = StructureVersion::factory()->create()->pages()->sole();

        $this->actingAs($user)->put(route('settings.templatePagePosition.update', [$template, $theirs]), ['position' => 1])
            ->assertNotFound();
    }
}
