<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\SetContactFavourite;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetContactFavouriteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_a_contact_a_favourite(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(SetContactFavourite::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'favourite' => true,
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_favourite' => true]);
    }

    #[Test]
    public function it_stops_marking_a_contact_a_favourite(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->favourite()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(SetContactFavourite::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'favourite' => false,
        ])->assertOk();

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_favourite' => false]);
    }
}
