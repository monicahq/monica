<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateContactRelationship;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateContactRelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_ends_a_relationship_and_keeps_its_start(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $relationship = Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'started_on' => '2007-05-10']);

        MonicaServer::actingAs($user)->tool(UpdateContactRelationship::class, [
            'vault' => $vault->uuid,
            'contact' => $relationship->contactA->uuid,
            'relationship' => $relationship->uuid,
            'status' => 'ended',
            'ended_on' => '2008-01-15',
        ])->assertOk()->assertHasNoErrors();

        $relationship->refresh();
        $this->assertSame('ended', $relationship->status->value);
        $this->assertSame('2007-05-10', $relationship->started_on?->format('Y-m-d'));
        $this->assertSame('2008-01-15', $relationship->ended_on?->format('Y-m-d'));
    }

    #[Test]
    public function it_refuses_to_name_somebody_with_a_record_of_their_own(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $relationship = Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(UpdateContactRelationship::class, [
            'vault' => $vault->uuid,
            'contact' => $relationship->contactA->uuid,
            'relationship' => $relationship->uuid,
            'first_name' => 'Pamela',
        ])->assertHasErrors();
    }
}
