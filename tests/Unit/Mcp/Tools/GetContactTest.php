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

    #[Test]
    public function it_reports_a_contact_that_never_existed_as_not_existing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(GetContact::class, [
            'vault' => $vault->uuid,
            'contact' => '3f6c1d90-4b27-4e85-a1c3-9d720f5b8e46',
        ])->assertHasErrors(['That contact does not exist.']);
    }

    #[Test]
    public function it_refuses_without_a_contact(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(GetContact::class, ['vault' => $vault->uuid])->assertHasErrors();
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.contacts.show', app(GetContact::class)->mirrors());
    }
}
