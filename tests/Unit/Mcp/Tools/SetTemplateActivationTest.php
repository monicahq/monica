<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\SetTemplateActivation;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetTemplateActivationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_switches_a_template_off_and_on(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(SetTemplateActivation::class, ['template' => $template->uuid, 'is_active' => false])->assertOk();
        $this->assertFalse($template->refresh()->is_active);

        EricaServer::actingAs($user)->tool(SetTemplateActivation::class, ['template' => $template->uuid, 'is_active' => true])->assertOk();
        $this->assertTrue($template->refresh()->is_active);
    }
}
