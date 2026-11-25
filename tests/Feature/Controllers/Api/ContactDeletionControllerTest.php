<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactDeletionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'attributes' => [
                'deleted',
                'kept',
            ],
        ],
    ];

    #[Test]
    public function it_deletes_the_people_given_and_says_who_was_kept(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->me()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $todd = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts", ['contacts' => [$todd->uuid, $me->uuid]])
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.deleted', [$todd->uuid])
            ->assertJsonPath('data.attributes.kept.0.contact', $me->uuid);

        $this->assertSoftDeleted($todd);
        $this->assertNotSoftDeleted($me);
    }

    #[Test]
    public function it_deletes_the_others_when_a_person_given_is_already_deleted(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $creed = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $creed->delete();
        $todd = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts", ['contacts' => [$creed->uuid, $todd->uuid]])
            ->assertOk()
            ->assertJsonPath('data.attributes.deleted', [$todd->uuid])
            ->assertJsonPath('data.attributes.kept', []);
    }

    #[Test]
    public function it_refuses_more_than_five_hundred_people(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $uuids = array_map(fn (): string => (string) Str::uuid(), range(1, 501));

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts", ['contacts' => $uuids])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contacts');
    }

    #[Test]
    public function it_refuses_a_person_of_another_vault(): void
    {
        $user = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $user->account_id]);
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $karen = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $stamford->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$scranton->uuid}/contacts", ['contacts' => [$karen->uuid]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contacts.0');

        $this->assertNotSoftDeleted($karen);
    }

    #[Test]
    public function it_does_not_find_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts", ['contacts' => [$contact->uuid]])
            ->assertNotFound();
    }
}
