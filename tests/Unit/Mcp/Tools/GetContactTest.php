<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Jobs\RecordContactVisit;
use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetContact;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_the_contact_asked_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael']);

        EricaServer::actingAs($user)->tool(GetContact::class, ['vault' => $vault->uuid, 'contact' => $contact->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Michael');
    }

    #[Test]
    public function it_writes_down_that_the_contact_was_read(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael']);

        EricaServer::actingAs($user)->tool(GetContact::class, ['vault' => $vault->uuid, 'contact' => $contact->uuid])->assertOk();

        Queue::assertPushedOn('low', RecordContactVisit::class);
    }
}
