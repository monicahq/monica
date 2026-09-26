<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\DeleteTemplate;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_an_unused_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(DeleteTemplate::class, ['template' => $template->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSoftDeleted($template);
    }

    #[Test]
    public function it_refuses_a_template_still_describing_records(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->published()->used(3)->create(['structure_id' => $template->structure->id]);

        EricaServer::actingAs($user)->tool(DeleteTemplate::class, ['template' => $template->uuid])
            ->assertHasErrors(['This template still renders']);

        $this->assertModelExists($template);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.templates.destroy', app(DeleteTemplate::class)->mirrors());
    }
}
