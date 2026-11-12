<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\DuplicateTemplate;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DuplicateTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_copies_a_template_under_a_new_name(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Salesman']);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        EricaServer::actingAs($user)->tool(DuplicateTemplate::class, ['template' => $template->uuid, 'name' => 'Regional manager'])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('templates', ['account_id' => $user->account_id, 'name' => 'Regional manager']);
    }
}
