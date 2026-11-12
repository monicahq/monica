<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Jobs\RecordContactVisit;
use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetMeContact;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetMeContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_resolves_the_contact_standing_for_the_account_holder(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->me()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Creed']);

        EricaServer::actingAs($user)->tool(GetMeContact::class, ['vault' => $vault->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Michael')
            ->assertDontSee('Creed');
    }

    #[Test]
    public function it_writes_down_that_the_contact_was_read(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->me()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael']);

        EricaServer::actingAs($user)->tool(GetMeContact::class, ['vault' => $vault->uuid])->assertOk();

        Queue::assertPushedOn('low', RecordContactVisit::class);
    }

    #[Test]
    public function it_says_so_when_the_vault_holds_no_such_contact(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(GetMeContact::class, ['vault' => $vault->uuid])
            ->assertHasErrors(['That vault holds no contact standing for you.']);
    }
}
