<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\UpdateTemplate;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_the_name_when_only_the_description_changes(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Dundie nominee']);

        EricaServer::actingAs($user)->tool(UpdateTemplate::class, [
            'template' => $template->uuid,
            'description' => 'Everybody who might win a Dundie.',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'name' => 'Dundie nominee',
            'description' => 'Everybody who might win a Dundie.',
        ]);
    }
}
