<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyContact;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Relationship;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_contact_softly(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Todd',
            'last_name' => 'Packer',
        ]);

        new DestroyContact(user: $user, contact: $contact)->execute();

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactDeletion
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($contact)
                && $job->parameters === ['name' => 'Todd Packer'],
        );
    }

    #[Test]
    public function it_takes_the_name_of_the_contact_out_of_the_records_that_named_them(): void
    {
        config(['scout.driver' => 'database']);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);
        Relationship::factory()->create(['vault_id' => $vault->id, 'contact_a_id' => $michael->id, 'contact_b_id' => $jan->id]);
        $dinner = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Dinner party']);
        LifeEventParticipant::factory()->create(['life_event_id' => $dinner->id, 'participant_id' => $jan->id]);

        $this->assertStringContainsStringIgnoringCase('Levinson', (string) DB::table('contacts')->where('id', $michael->id)->value('search_linked_names'));
        $this->assertStringContainsStringIgnoringCase('Levinson', (string) DB::table('life_events')->where('id', $dinner->id)->value('search_linked_names'));

        new DestroyContact(user: $user, contact: $jan)->execute();

        $this->assertStringNotContainsStringIgnoringCase('Levinson', (string) DB::table('contacts')->where('id', $michael->id)->value('search_linked_names'));
        $this->assertStringNotContainsStringIgnoringCase('Levinson', (string) DB::table('life_events')->where('id', $dinner->id)->value('search_linked_names'));
    }

    #[Test]
    public function it_takes_the_contact_out_of_the_vault_and_version_counts(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 4]);
        $version = StructureVersion::factory()->published()->used(3)->create();
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        new DestroyContact(user: $user, contact: $contact)->execute();

        $this->assertSame(3, $vault->fresh()->contact_count);
        $this->assertSame(2, $version->fresh()->record_count);
    }

    #[Test]
    public function it_refuses_a_contact_another_request_deleted_first(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 4]);
        $version = StructureVersion::factory()->published()->used(3)->create();
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        new DestroyContact(user: $user, contact: Contact::query()->findOrFail($contact->id))->execute();

        $this->assertThrows(
            fn () => new DestroyContact(user: $user, contact: $contact)->execute(),
            ModelNotFoundException::class,
        );

        $this->assertSame(3, $vault->fresh()->contact_count);
        $this->assertSame(2, $version->fresh()->record_count);
        $this->assertCount(1, Queue::pushed(LogUserAction::class));
    }

    #[Test]
    public function it_never_takes_a_count_below_zero(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 0]);
        $version = StructureVersion::factory()->published()->create();
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        new DestroyContact(user: $user, contact: $contact)->execute();

        $this->assertSame(0, $vault->fresh()->contact_count);
        $this->assertSame(0, $version->fresh()->record_count);
    }

    #[Test]
    public function it_refuses_to_delete_the_contact_representing_the_user(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->me()->create(['account_id' => $user->account_id]);

        $this->expectException(AuthorizationException::class);

        new DestroyContact(user: $user, contact: $contact)->execute();
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyContact(user: $user, contact: $contact)->execute();
    }

    #[Test]
    public function it_revokes_the_links_opened_onto_the_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);
        $shareLink = ShareLink::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'shareable_type' => Contact::class,
            'shareable_id' => $contact->id,
        ]);
        $elsewhere = ShareLink::factory()->create();

        new DestroyContact(user: $user, contact: $contact)->execute();

        $this->assertNotNull($shareLink->refresh()->revoked_at);
        $this->assertNull($elsewhere->refresh()->revoked_at);
    }

    #[Test]
    public function it_silences_every_reminder_about_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->reminderAbout($user);

        new DestroyContact(user: $user, contact: $reminder->remindable)->execute();

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordDeleted->value,
            'deleted_with_type' => Contact::class,
            'deleted_with_id' => $reminder->remindable_id,
        ]);
        $this->assertSoftDeleted('reminders', ['id' => $reminder->id]);
    }

    #[Test]
    public function it_sends_what_is_theirs_to_the_trash_with_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan']);
        $relationship = Relationship::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'contact_b_id' => $jan->id]);
        $membership = GroupMember::factory()->create([
            'group_id' => Group::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id])->id,
            'contact_id' => $jan->id,
        ]);
        $ownLifeEvent = LifeEvent::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $ownLifeEvent->id, 'participant_id' => $jan->id]);
        $sharedLifeEvent = LifeEvent::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $sharedLifeEvent->id, 'participant_id' => $jan->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $sharedLifeEvent->id]);

        new DestroyContact(user: $user, contact: $jan)->execute();

        foreach (['relationships' => $relationship, 'group_members' => $membership, 'life_events' => $ownLifeEvent] as $table => $record) {
            $this->assertSoftDeleted($table, ['id' => $record->id, 'deleted_with_type' => Contact::class, 'deleted_with_id' => $jan->id]);
        }

        $this->assertNotSoftDeleted('life_events', ['id' => $sharedLifeEvent->id]);
    }

    #[Test]
    public function it_keeps_every_way_the_name_is_written_on_a_deleted_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Todd', 'last_name' => 'Packer']);
        $shown = $contact->display_name_representation_id;

        new DestroyContact(user: $user, contact: $contact)->execute();

        $this->assertDatabaseHas('contact_name_representations', ['id' => $shown, 'contact_id' => $contact->id]);
        $this->assertSame($shown, Contact::withTrashed()->find($contact->id)?->display_name_representation_id);
    }
}
