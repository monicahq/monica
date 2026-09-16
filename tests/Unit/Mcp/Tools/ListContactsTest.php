<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListContacts;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListContactsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_the_contacts_of_the_vault_it_was_given(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael']);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $vault->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Michael');
    }

    #[Test]
    public function it_leaves_out_the_contacts_of_another_vault(): void
    {
        $user = User::factory()->create();
        $asked = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $elsewhere->id, 'account_id' => $user->account_id, 'first_name' => 'Creed']);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $asked->uuid])
            ->assertOk()
            ->assertDontSee('Creed');
    }

    #[Test]
    public function it_narrows_the_list_to_a_search(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $vault->uuid, 'query' => 'Beesly'])
            ->assertOk()
            ->assertSee('Beesly')
            ->assertDontSee('Scott');
    }

    #[Test]
    public function it_answers_an_empty_vault_without_erroring(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $vault->uuid])
            ->assertOk()
            ->assertHasNoErrors();
    }

    #[Test]
    public function it_refuses_without_a_vault(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(ListContacts::class, [])->assertHasErrors();
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.contacts', app(ListContacts::class)->mirrors());
    }
}
