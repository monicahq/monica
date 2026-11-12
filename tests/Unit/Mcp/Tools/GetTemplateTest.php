<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetTemplate;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_a_template_of_the_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Dundie nominee']);

        EricaServer::actingAs($user)->tool(GetTemplate::class, ['template' => $template->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Dundie nominee');
    }
}
