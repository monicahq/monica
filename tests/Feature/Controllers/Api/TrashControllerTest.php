<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Actions\DestroyContact;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrashControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'kind',
            'title',
            'type_label',
            'context',
            'deleted_at',
            'deleted_for_good_at',
            'deleted_with',
            'can_be_restored',
            'obstacle',
        ],
    ];

    #[Test]
    public function it_lists_the_trash_of_a_vault(): void
    {
        Queue::fake();

        [$user, $vault, $jan, $lifeEvent] = $this->janDeletedWithALifeEvent();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/trash")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']])
            ->assertJsonPath('data.0.id', $jan->uuid)
            ->assertJsonPath('data.0.attributes.kind', 'contact');

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/trash?kind=life-event")
            ->assertOk()
            ->assertJsonPath('data.0.id', $lifeEvent->uuid)
            ->assertJsonPath('data.0.attributes.deleted_with.id', $jan->uuid)
            ->assertJsonPath('data.0.attributes.can_be_restored', false);
    }

    #[Test]
    public function it_does_not_list_the_trash_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/trash")->assertNotFound();
    }

    #[Test]
    public function it_refuses_an_unknown_kind(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/trash?kind=possession")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kind');
    }

    #[Test]
    public function it_restores_an_item_and_says_what_came_back_with_it(): void
    {
        Queue::fake();

        [$user, $vault, $jan] = $this->janDeletedWithALifeEvent();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/trash/contact/{$jan->uuid}/restoration")
            ->assertOk()
            ->assertJsonPath('data.id', $jan->uuid)
            ->assertJsonPath('data.attributes.restored_with', [['kind' => 'life-event', 'count' => 1]]);

        $this->assertNotSoftDeleted($jan);
    }

    #[Test]
    public function it_refuses_to_restore_an_item_waiting_for_its_owner(): void
    {
        Queue::fake();

        [$user, $vault, , $lifeEvent] = $this->janDeletedWithALifeEvent();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/trash/life-event/{$lifeEvent->uuid}/restoration")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('item');
    }

    #[Test]
    public function it_deletes_an_item_for_good(): void
    {
        Queue::fake();

        [$user, $vault, $jan, $lifeEvent] = $this->janDeletedWithALifeEvent();

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/trash/contact/{$jan->uuid}")->assertNoContent();

        $this->assertModelMissing($jan);
        $this->assertModelMissing($lifeEvent);
    }

    #[Test]
    public function it_empties_the_trash(): void
    {
        Queue::fake();

        [$user, $vault, $jan] = $this->janDeletedWithALifeEvent();

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/trash")->assertNoContent();

        $this->assertModelMissing($jan);
    }

    /** @return array{0: User, 1: Vault, 2: Contact, 3: LifeEvent} */
    private function janDeletedWithALifeEvent(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan']);
        $lifeEvent = LifeEvent::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $jan->id]);

        new DestroyContact(user: $user, contact: $jan)->execute();

        return [$user, $vault, $jan, $lifeEvent];
    }
}
