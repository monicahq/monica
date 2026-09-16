<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\UpdateContactName;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateContactNameTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_corrects_the_name(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        EricaServer::actingAs($user)->tool(UpdateContactName::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'first_name' => 'Pam',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'first_name' => 'Pam']);
    }

    #[Test]
    public function it_keeps_the_parts_of_the_name_it_was_not_given(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        EricaServer::actingAs($user)->tool(UpdateContactName::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'first_name' => 'Michel',
        ])->assertOk();

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'last_name' => 'Scott']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.contacts.name.update', app(UpdateContactName::class)->mirrors());
    }
}
