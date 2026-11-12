<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\CreateTemplate;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_template_for_contacts(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(CreateTemplate::class, [
            'name' => 'Dundie nominee',
            'applies_to' => 'contact',
        ])->assertOk()->assertHasNoErrors()->assertSee('Dundie nominee');

        $this->assertDatabaseHas('templates', ['account_id' => $user->account_id, 'name' => 'Dundie nominee']);
    }

    #[Test]
    public function it_creates_a_template_for_a_kind_of_entity(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(CreateTemplate::class, [
            'name' => 'Paper supplier',
            'applies_to' => 'entity',
            'entity_type' => $entityType->uuid,
        ])->assertOk()->assertHasNoErrors();

        $template = Template::query()->where('account_id', $user->account_id)->where('name', 'Paper supplier')->sole();
        $this->assertSame($entityType->id, $template->structure->entity_type_id);
    }

    #[Test]
    public function it_refuses_an_entity_template_without_an_entity_type(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(CreateTemplate::class, [
            'name' => 'Paper supplier',
            'applies_to' => 'entity',
        ])->assertHasErrors(['entity type field is required']);

        $this->assertDatabaseMissing('templates', ['name' => 'Paper supplier']);
    }
}
