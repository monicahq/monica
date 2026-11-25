<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyContact;
use App\Actions\RestoreTrashItem;
use App\Enums\TrashItemKindEnum;
use App\Exceptions\CannotRestoreFromTheTrash;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreTrashItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_restores_a_person_and_says_what_came_back_with_them(): void
    {
        Queue::fake();

        [$user, $vault, $jan, $lifeEvent] = $this->janDeletedWithALifeEvent();

        $action = new RestoreTrashItem(user: $user, vault: $vault, kind: TrashItemKindEnum::Contact, uuid: $jan->uuid);
        $item = $action->execute();

        $this->assertTrue($item->record->is($jan));
        $this->assertNotSoftDeleted($jan);
        $this->assertNotSoftDeleted($lifeEvent);
        $this->assertSame(['life-event' => 1, 'relationship' => 1], $action->broughtBack());
    }

    #[Test]
    public function it_refuses_an_item_waiting_for_its_owner(): void
    {
        Queue::fake();

        [$user, $vault, , $lifeEvent] = $this->janDeletedWithALifeEvent();

        $this->expectException(CannotRestoreFromTheTrash::class);

        new RestoreTrashItem(user: $user, vault: $vault, kind: TrashItemKindEnum::LifeEvent, uuid: $lifeEvent->uuid)->execute();
    }

    #[Test]
    public function it_restores_a_life_event_deleted_on_its_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $lifeEvent = LifeEvent::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id]);
        $lifeEvent->delete();

        new RestoreTrashItem(user: $user, vault: $vault, kind: TrashItemKindEnum::LifeEvent, uuid: $lifeEvent->uuid)->execute();

        $this->assertNotSoftDeleted($lifeEvent);
    }

    #[Test]
    public function it_answers_not_found_for_an_item_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['account_id' => $user->account_id, 'deleted_at' => now()]);

        $this->expectException(ModelNotFoundException::class);

        new RestoreTrashItem(user: $user, vault: $vault, kind: TrashItemKindEnum::Contact, uuid: $toby->uuid)->execute();
    }

    /** @return array{0: User, 1: Vault, 2: Contact, 3: LifeEvent} */
    private function janDeletedWithALifeEvent(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);
        Relationship::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'contact_a_id' => $jan->id]);
        $lifeEvent = LifeEvent::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $jan->id]);

        new DestroyContact(user: $user, contact: $jan)->execute();

        return [$user, $vault, $jan, $lifeEvent];
    }
}
