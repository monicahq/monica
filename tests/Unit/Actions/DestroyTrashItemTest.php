<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyContact;
use App\Actions\DestroyTrashItem;
use App\Enums\TrashItemKindEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyTrashItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_person_for_good_with_what_went_with_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);
        $lifeEvent = LifeEvent::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $jan->id]);
        $photo = File::factory()->used()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        FieldValue::factory()->create(['valuable_id' => $jan->id, 'value_file_id' => $photo->id]);
        $pointing = FieldValue::factory()->create(['related_contact_id' => $jan->id, 'value_string' => null]);
        new DestroyContact(user: $user, contact: $jan)->execute();

        new DestroyTrashItem(user: $user, vault: $vault, kind: TrashItemKindEnum::Contact, uuid: $jan->uuid)->execute();

        $this->assertModelMissing($jan);
        $this->assertModelMissing($lifeEvent);
        $this->assertModelMissing($pointing);
        $this->assertDatabaseMissing('field_values', ['valuable_type' => Contact::class, 'valuable_id' => $jan->id]);
        $this->assertSame(0, $photo->fresh()->reference_count);
        $this->assertNotNull($photo->fresh()->orphaned_at);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TrashItemDestruction
                && $job->parameters === ['name' => 'Jan Levinson', 'vault' => $vault->name],
        );
    }

    #[Test]
    public function it_lets_go_of_the_file_of_an_attachment(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $attachment = GroupFile::factory()->create(['group_id' => $group->id, 'deleted_at' => now()]);

        new DestroyTrashItem(user: $user, vault: $vault, kind: TrashItemKindEnum::GroupFile, uuid: $attachment->uuid)->execute();

        $this->assertModelMissing($attachment);
        $this->assertSame(0, $attachment->file->fresh()->reference_count);
    }

    #[Test]
    public function it_answers_not_found_for_an_item_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['account_id' => $user->account_id, 'deleted_at' => now()]);

        $this->expectException(ModelNotFoundException::class);

        new DestroyTrashItem(user: $user, vault: $vault, kind: TrashItemKindEnum::Contact, uuid: $toby->uuid)->execute();
    }

    #[Test]
    public function it_deletes_for_good_a_life_event_left_with_nobody_in_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);
        $lifeEvent = LifeEvent::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $jan->id]);
        $lifeEvent->delete();

        new DestroyTrashItem(user: $user, vault: $vault, kind: TrashItemKindEnum::Contact, uuid: $jan->uuid)->execute();

        $this->assertModelMissing($lifeEvent);
    }

    #[Test]
    public function it_keeps_a_life_event_in_the_trash_while_somebody_is_still_in_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);
        $michael = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $lifeEvent = LifeEvent::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $jan->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $michael->id]);
        $lifeEvent->delete();

        new DestroyTrashItem(user: $user, vault: $vault, kind: TrashItemKindEnum::Contact, uuid: $jan->uuid)->execute();

        $this->assertSoftDeleted($lifeEvent);
    }
}
