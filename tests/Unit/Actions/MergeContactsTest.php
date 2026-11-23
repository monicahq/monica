<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\MergeContacts;
use App\Actions\PreviewContactMerge;
use App\Enums\AccountImportStatusEnum;
use App\Enums\ContactNameKindEnum;
use App\Enums\DatePrecisionEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\SnapshotReasonEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotAbsorbTheMeContact;
use App\Exceptions\ContactChangedSinceThePreview;
use App\Exceptions\VaultIsBeingImportedInto;
use App\Jobs\LogUserAction;
use App\Models\AccountImport;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\ContactNameRepresentation;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\RecordSnapshot;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Reminder;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MergeContactsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_the_record_of_the_merge(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertInstanceOf(ContactMerge::class, $merge);
        $this->assertDatabaseHas('contact_merges', [
            'id' => $merge->id,
            'kept_contact_id' => $jim->id,
            'merged_contact_id' => $james->id,
            'merged_by_user_id' => $user->id,
            'undone_at' => null,
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactsMerged);
    }

    #[Test]
    public function it_saves_the_state_of_the_survivor_before_writing_anything(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $snapshot = RecordSnapshot::query()->findOrFail($merge->snapshot_id);

        $this->assertEquals(SnapshotReasonEnum::Merge, $snapshot->reason);
        $this->assertEquals($jim->id, $snapshot->snapshotable_id);
    }

    #[Test]
    public function it_soft_deletes_the_absorbed_contact_rather_than_destroying_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertSoftDeleted($james);
    }

    #[Test]
    public function it_brings_over_a_value_the_survivor_does_not_hold(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $value = $this->valueFor($james, $field, 'james@dundermifflin.com');

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'valuable_id' => $jim->id,
            'is_detached' => false,
        ]);
    }

    #[Test]
    public function it_keeps_the_survivors_value_when_the_user_says_so(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $mine = $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $theirs = $this->valueFor($james, $field, 'james@dundermifflin.com');

        new MergeContacts(
            user: $user,
            kept: $jim,
            absorbed: $james,
            resolutions: [['field_key' => $field->key, 'row' => null, 'keep' => 'kept']],
        )->execute();

        $this->assertDatabaseHas('field_values', ['id' => $mine->id, 'valuable_id' => $jim->id, 'is_detached' => false]);
        $this->assertDatabaseHas('field_values', ['id' => $theirs->id, 'valuable_id' => $james->id]);
    }

    #[Test]
    public function it_reads_a_row_sent_as_text_the_way_a_form_sends_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $this->rowWithValueFor($jim, $repeater, $field, 'jim@dundermifflin.com');
        $theirs = $this->rowWithValueFor($james, $repeater, $field, 'jim@dundermifflin.com');

        new MergeContacts(
            user: $user,
            kept: $jim,
            absorbed: $james,
            resolutions: [['field_key' => $repeater->key, 'row' => (string) $theirs->id, 'keep' => 'kept']],
        )->execute();

        $this->assertDatabaseHas('field_value_rows', ['id' => $theirs->id, 'valuable_id' => $james->id]);
    }

    #[Test]
    public function it_takes_the_other_value_when_the_user_says_so_and_sets_the_survivors_aside(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $mine = $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $theirs = $this->valueFor($james, $field, 'james@dundermifflin.com');

        new MergeContacts(
            user: $user,
            kept: $jim,
            absorbed: $james,
            resolutions: [['field_key' => $field->key, 'row' => null, 'keep' => 'other']],
        )->execute();

        $this->assertDatabaseHas('field_values', ['id' => $theirs->id, 'valuable_id' => $jim->id, 'is_detached' => false]);
        $this->assertDatabaseHas('field_values', ['id' => $mine->id, 'valuable_id' => $jim->id, 'is_detached' => true]);
    }

    #[Test]
    public function it_stops_the_reminder_on_the_survivors_value_when_the_other_value_is_taken(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $mine = Reminder::factory()->create(['field_value_id' => $this->valueFor($jim, $field, 'jim@dundermifflin.com')->id]);
        $theirs = Reminder::factory()->create(['field_value_id' => $this->valueFor($james, $field, 'james@dundermifflin.com')->id]);

        new MergeContacts(
            user: $user,
            kept: $jim,
            absorbed: $james,
            resolutions: [['field_key' => $field->key, 'row' => null, 'keep' => 'other']],
        )->execute();

        $this->assertDatabaseHas('reminders', [
            'id' => $mine->id,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::ValueCleared,
        ]);
        $this->assertDatabaseHas('reminders', ['id' => $theirs->id, 'remindable_id' => $jim->id, 'is_active' => true]);
    }

    #[Test]
    public function it_sets_aside_a_value_the_surviving_structure_cannot_carry(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $mine = $this->publishedContactVersion($user);
        $theirs = $this->publishedContactVersion($user);
        $onlyTheirs = $this->fieldCarriedBy($theirs);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = $this->contactIn($vault, $mine, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $theirs, 'James', 'Halpert');
        $value = $this->valueFor($james, $onlyTheirs, 'Loyalty 4471');

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'valuable_id' => $jim->id,
            'is_detached' => true,
        ]);
    }

    #[Test]
    public function it_keeps_a_reminder_written_by_hand_about_the_absorbed_contact_going(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $reminder = Reminder::factory()->manual($james, now()->addWeek()->toDateString())->create();

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'remindable_id' => $jim->id, 'is_active' => true]);
    }

    #[Test]
    public function it_moves_the_reminders_and_the_share_links(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $reminder = $this->reminderAbout($user, $james);
        $link = ShareLink::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $james->vault_id,
            'shareable_type' => Contact::class,
            'shareable_id' => $james->id,
        ]);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'remindable_id' => $jim->id]);
        $this->assertDatabaseHas('share_links', ['id' => $link->id, 'shareable_id' => $jim->id]);
    }

    #[Test]
    public function it_repoints_what_other_contacts_held_onto_the_absorbed_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $pam = $this->contactIn($jim->vault, $version, 'Pam', 'Beesly');

        $pointer = FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $pam->id,
            'related_contact_id' => $james->id,
            'value_string' => null,
        ]);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertDatabaseHas('field_values', ['id' => $pointer->id, 'related_contact_id' => $jim->id]);
    }

    #[Test]
    public function it_takes_the_photo_of_the_absorbed_contact_when_the_survivor_has_none(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $file = File::factory()->create(['account_id' => $user->account_id, 'vault_id' => $james->vault_id]);
        $james->avatar_file_id = $file->id;
        $james->save();

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertEquals($file->id, $jim->refresh()->avatar_file_id);
    }

    #[Test]
    public function it_stops_the_survivor_being_partial(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $jim->is_partial = true;
        $jim->save();

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertFalse($jim->refresh()->is_partial);
    }

    #[Test]
    public function it_refuses_to_make_one_full_contact_of_two_partial_ones_past_the_ceiling(): void
    {
        Queue::fake();
        config(['monica.self_hosted' => false, 'monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $jim->update(['is_partial' => true]);
        $james->update(['is_partial' => true]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $jim->vault_id]);

        try {
            new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
            $this->fail('The merge made a counted contact past the ceiling of the free plan.');
        } catch (AuthorizationException) {
            $this->assertTrue($jim->refresh()->is_partial);
            $this->assertNotSoftDeleted($james);
            $this->assertDatabaseMissing('contact_merges', ['kept_contact_id' => $jim->id]);
        }
    }

    #[Test]
    public function it_makes_one_full_contact_of_two_partial_ones_below_the_ceiling(): void
    {
        Queue::fake();
        config(['monica.self_hosted' => false, 'monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $jim->update(['is_partial' => true]);
        $james->update(['is_partial' => true]);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertFalse($jim->refresh()->is_partial);
    }

    #[Test]
    public function it_records_what_moved_so_the_merge_can_be_reversed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $value = $this->valueFor($james, $field, 'james@dundermifflin.com');

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertContains(
            ['kind' => 'value_moved', 'id' => $value->id, 'from' => $james->id],
            $merge->ledger
        );
    }

    #[Test]
    public function it_starts_again_from_the_database_when_a_deadlock_rolls_an_attempt_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $jim->is_partial = true;
        $jim->save();
        $value = $this->valueFor($james, $field, 'james@dundermifflin.com');
        $name = $james->nameRepresentations()->sole();

        $deadlocked = false;
        ContactMerge::creating(function () use (&$deadlocked): void {
            if ($deadlocked) {
                return;
            }

            $deadlocked = true;

            throw new QueryException('testing', 'insert into contact_merges', [], new PDOException('Deadlock found when trying to get lock'));
        });

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james);

        try {
            $merge->execute();
            $this->fail('The first attempt was expected to deadlock.');
        } catch (DeadlockException) {
        }

        $record = $merge->execute();

        $this->assertSame(
            [
                ['kind' => 'name_representation_moved', 'id' => $name->id, 'from' => $james->id, 'position' => 1],
                ['kind' => 'value_moved', 'id' => $value->id, 'from' => $james->id],
                ['kind' => 'partial_cleared', 'id' => $jim->id],
            ],
            $record->ledger
        );
        $this->assertFalse($jim->refresh()->is_partial);
        $this->assertSoftDeleted($james);
        $this->assertSame(1, RecordSnapshot::query()->where('reason', SnapshotReasonEnum::Merge)->count());
    }

    #[Test]
    public function it_refuses_a_resolution_the_preview_never_asked_about(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        $this->expectException(ValidationException::class);

        new MergeContacts(
            user: $user,
            kept: $jim,
            absorbed: $james,
            resolutions: [['field_key' => 'invented', 'row' => null, 'keep' => 'kept']],
        )->execute();
    }

    #[Test]
    public function it_refuses_a_merge_leaving_a_difference_undecided(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $this->valueFor($james, $field, 'james@dundermifflin.com');

        $this->expectException(ValidationException::class);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
    }

    #[Test]
    public function it_refuses_a_merge_confirmed_against_a_state_that_changed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $value = $this->valueFor($james, $field, 'james@dundermifflin.com');

        $stale = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute()['fingerprint'];

        $value->value_string = 'jamesh@dundermifflin.com';
        $value->save();

        $this->expectException(ContactChangedSinceThePreview::class);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james, fingerprint: $stale)->execute();
    }

    #[Test]
    public function it_refuses_a_merge_while_an_import_writes_into_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        [$dwight, $imported] = $this->twoContactsOf($user, $version);
        AccountImport::factory()->create(['account_id' => $user->account_id, 'status' => AccountImportStatusEnum::Writing, 'vault_id' => $dwight->vault_id]);

        try {
            new MergeContacts(user: $user, kept: $imported, absorbed: $dwight)->execute();
            $this->fail('A merge ran while an import was writing into the vault.');
        } catch (VaultIsBeingImportedInto) {
            //
        }

        $this->assertModelExists($dwight);
        $this->assertSame(0, ContactMerge::query()->count());
    }

    #[Test]
    public function it_runs_when_the_fingerprint_still_matches(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        $fingerprint = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute()['fingerprint'];

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james, fingerprint: $fingerprint)->execute();

        $this->assertSoftDeleted($james);
        $this->assertNotNull($merge->id);
    }

    #[Test]
    public function it_refuses_a_contact_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $stranger = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new MergeContacts(user: $user, kept: $contact, absorbed: $stranger)->execute();
    }

    #[Test]
    public function it_refuses_a_contact_of_another_vault_of_the_same_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Contact::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new MergeContacts(user: $user, kept: $contact, absorbed: $elsewhere)->execute();
    }

    #[Test]
    public function it_refuses_absorbing_the_contact_representing_the_user(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'is_me' => true,
        ]);

        $this->expectException(CannotAbsorbTheMeContact::class);

        new MergeContacts(user: $user, kept: $contact, absorbed: $me)->execute();
    }

    #[Test]
    public function it_leaves_the_snapshots_of_the_absorbed_contact_where_they_are(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $theirs = RecordSnapshot::factory()->create([
            'snapshotable_type' => Contact::class,
            'snapshotable_id' => $james->id,
        ]);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertDatabaseHas('record_snapshots', ['id' => $theirs->id, 'snapshotable_id' => $james->id]);
    }

    #[Test]
    public function it_moves_the_memberships_of_the_absorbed_contact_to_the_survivor(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $group = Group::factory()->create(['vault_id' => $jim->vault_id, 'name' => 'Scranton branch', 'updated_at' => '2007-01-01 00:00:00']);
        $membership = GroupMember::factory()->former()->create(['group_id' => $group->id, 'contact_id' => $james->id]);

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertDatabaseHas('group_members', ['id' => $membership->id, 'contact_id' => $jim->id, 'deleted_at' => null]);
        $this->assertContains(['kind' => 'membership_moved', 'id' => $membership->id, 'from' => $james->id], $merge->ledger);
        $this->assertTrue($group->fresh()->updated_at->isAfter('2007-01-01'));
    }

    #[Test]
    public function it_moves_the_loans_of_the_absorbed_contact_to_the_survivor(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $mug = Possession::factory()->create(['vault_id' => $jim->vault_id]);
        $loan = PossessionLoan::factory()->create(['possession_id' => $mug->id, 'contact_id' => $james->id]);

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertDatabaseHas('possession_loans', ['id' => $loan->id, 'contact_id' => $jim->id]);
        $this->assertContains(['kind' => 'loan_moved', 'id' => $loan->id, 'from' => $james->id], $merge->ledger);
    }

    #[Test]
    public function it_folds_two_current_memberships_of_one_group_into_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $group = Group::factory()->create(['vault_id' => $jim->vault_id, 'name' => 'Scranton branch', 'updated_at' => '2007-01-01 00:00:00']);
        $mine = GroupMember::factory()->create([
            'group_id' => $group->id,
            'contact_id' => $jim->id,
            'role' => 'Salesman',
            'joined_on' => '2005-03-24',
            'joined_precision' => DatePrecisionEnum::Day,
        ]);
        $theirs = GroupMember::factory()->create([
            'group_id' => $group->id,
            'contact_id' => $james->id,
            'role' => 'Assistant to the regional manager',
            'joined_on' => '2001-01-01',
            'joined_precision' => DatePrecisionEnum::Year,
        ]);

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $mine->refresh();
        $this->assertEquals('2001-01-01', $mine->joined_on->format('Y-m-d'));
        $this->assertEquals(DatePrecisionEnum::Year, $mine->joined_precision);
        $this->assertEquals('Salesman', $mine->role);
        $this->assertSoftDeleted($theirs);
        $this->assertContains([
            'kind' => 'membership_folded',
            'id' => $mine->id,
            'dropped' => $theirs->id,
            'joined_on' => '2005-03-24',
            'joined_precision' => 'day',
            'role' => 'Salesman',
        ], $merge->ledger);
        $this->assertTrue($group->fresh()->updated_at->isAfter('2007-01-01'));
    }

    #[Test]
    public function it_takes_the_role_and_the_known_join_date_of_the_absorbed_membership_when_the_survivor_has_none(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $group = Group::factory()->create(['vault_id' => $jim->vault_id, 'name' => 'Party planning committee']);
        $mine = GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $jim->id, 'role' => null]);
        GroupMember::factory()->create([
            'group_id' => $group->id,
            'contact_id' => $james->id,
            'role' => 'Member',
            'joined_on' => '2006-05-01',
            'joined_precision' => DatePrecisionEnum::Month,
        ]);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $mine->refresh();
        $this->assertEquals('2006-05-01', $mine->joined_on->format('Y-m-d'));
        $this->assertEquals(DatePrecisionEnum::Month, $mine->joined_precision);
        $this->assertEquals('Member', $mine->role);
    }

    #[Test]
    public function it_keeps_the_survivors_known_join_date_over_an_unknown_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $group = Group::factory()->create(['vault_id' => $jim->vault_id, 'name' => 'Halpert household']);
        $mine = GroupMember::factory()->create([
            'group_id' => $group->id,
            'contact_id' => $jim->id,
            'joined_on' => '2009-10-17',
            'joined_precision' => DatePrecisionEnum::Day,
        ]);
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $james->id]);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertEquals('2009-10-17', $mine->fresh()->joined_on->format('Y-m-d'));
    }

    #[Test]
    public function it_moves_the_relationships_of_the_absorbed_contact_to_the_survivor(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $pam = Contact::factory()->create(['account_id' => $jim->account_id, 'vault_id' => $jim->vault_id, 'first_name' => 'Pam']);
        $spouse = RelationshipType::factory()->create(['account_id' => $user->account_id, 'is_symmetric' => true]);
        $relationship = Relationship::factory()->create([
            'vault_id' => $jim->vault_id,
            'relationship_type_id' => $spouse->id,
            'contact_a_id' => $pam->id,
            'contact_b_id' => $james->id,
        ]);

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertDatabaseHas('relationships', ['id' => $relationship->id, 'contact_a_id' => $pam->id, 'contact_b_id' => $jim->id, 'deleted_at' => null]);
        $this->assertContains(['kind' => 'relationship_moved', 'id' => $relationship->id, 'contact_a_id' => $pam->id, 'contact_b_id' => $james->id], $merge->ledger);
    }

    #[Test]
    public function it_drops_a_relationship_between_the_two_contacts_and_one_the_survivor_already_has(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $pam = Contact::factory()->create(['account_id' => $jim->account_id, 'vault_id' => $jim->vault_id, 'first_name' => 'Pam']);
        $spouse = RelationshipType::factory()->create(['account_id' => $user->account_id, 'is_symmetric' => true]);
        $mine = Relationship::factory()->create(['vault_id' => $jim->vault_id, 'relationship_type_id' => $spouse->id, 'contact_a_id' => $jim->id, 'contact_b_id' => $pam->id]);
        $twin = Relationship::factory()->create(['vault_id' => $jim->vault_id, 'relationship_type_id' => $spouse->id, 'contact_a_id' => $pam->id, 'contact_b_id' => $james->id]);
        $between = Relationship::factory()->create(['vault_id' => $jim->vault_id, 'relationship_type_id' => $spouse->id, 'contact_a_id' => $jim->id, 'contact_b_id' => $james->id]);

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertNotSoftDeleted($mine);
        $this->assertSoftDeleted($twin);
        $this->assertSoftDeleted($between);
        $this->assertContains(['kind' => 'relationship_dropped', 'id' => $twin->id], $merge->ledger);
        $this->assertContains(['kind' => 'relationship_dropped', 'id' => $between->id], $merge->ledger);
    }

    #[Test]
    public function it_moves_the_life_event_participations_of_the_absorbed_contact_to_the_survivor(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $dundies = LifeEvent::factory()->create(['vault_id' => $jim->vault_id]);
        $wedding = LifeEvent::factory()->create(['vault_id' => $jim->vault_id]);
        $moved = LifeEventParticipant::factory()->create(['life_event_id' => $dundies->id, 'participant_id' => $james->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $wedding->id, 'participant_id' => $jim->id]);
        $dropped = LifeEventParticipant::factory()->create(['life_event_id' => $wedding->id, 'participant_id' => $james->id, 'role' => 'Groom']);

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertDatabaseHas('life_event_participants', ['id' => $moved->id, 'participant_id' => $jim->id]);
        $this->assertModelMissing($dropped);
        $this->assertSame(2, $jim->fresh()->life_event_count);
        $this->assertContains(['kind' => 'participation_moved', 'id' => $moved->id], $merge->ledger);
    }

    /** @return array{0: Contact, 1: Contact} */
    private function twoContactsOf(User $user, ?StructureVersion $version = null): array
    {
        $version ??= $this->publishedContactVersion($user);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [
            $this->contactIn($vault, $version, 'Jim', 'Halpert'),
            $this->contactIn($vault, $version, 'James', 'Halpert'),
        ];
    }

    private function contactIn(Vault $vault, StructureVersion $version, string $first, string $last): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    private function publishedContactVersion(User $user): StructureVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        return StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
    }

    private function fieldCarriedBy(StructureVersion $version): Field
    {
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $version->account_id]);
        $field = Field::factory()->create([
            'account_id' => $version->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->status = VersionStatusEnum::Draft;
        $version->save();

        StructureVersionSection::factory()->create([
            'account_id' => $version->account_id,
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->status = VersionStatusEnum::Published;
        $version->save();

        return $field;
    }

    private function rowWithValueFor(Contact $contact, Field $repeater, Field $field, string $value): FieldValueRow
    {
        $row = FieldValueRow::factory()->create([
            'account_id' => $contact->account_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $repeater->id,
        ]);

        FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_value_row_id' => $row->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => $value,
        ]);

        return $row;
    }

    private function valueFor(Contact $contact, Field $field, string $value): FieldValue
    {
        return FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => $value,
        ]);
    }

    #[Test]
    public function it_brings_over_every_way_the_other_name_is_written_and_shows_the_one_chosen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $kana = ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $james->id, 'script_code' => 'Kana', 'formatted_name' => 'ジェームズ・ハルパート']);

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james, resolutions: [
            ['field_key' => '@display_name', 'row' => null, 'keep' => 'other'],
        ])->execute();

        $jim->refresh();
        $this->assertSame('James Halpert', $jim->name);
        $this->assertSame(3, $jim->nameRepresentations()->count());
        $this->assertSame($jim->id, $kana->refresh()->contact_id);
        $this->assertContains('display_name_replaced', array_column($merge->ledger, 'kind'));
    }

    #[Test]
    public function it_keeps_the_survivors_name_when_nobody_answers_which_to_show(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertSame('Jim Halpert', $jim->refresh()->name);
        $this->assertSame(2, $jim->nameRepresentations()->count());
    }

    #[Test]
    public function it_leaves_a_way_of_writing_the_name_both_share_with_the_absorbed_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $shared = ['kind' => ContactNameKindEnum::Transliteration, 'script_code' => 'Kana', 'formatted_name' => 'ジム・ハルパート', 'given_name' => null, 'family_name' => null];
        ContactNameRepresentation::factory()->create([...$shared, 'contact_id' => $jim->id]);
        $theirs = ContactNameRepresentation::factory()->create([...$shared, 'contact_id' => $james->id]);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertSame($james->id, $theirs->refresh()->contact_id);
        $this->assertSame(3, $jim->nameRepresentations()->count());
    }

    #[Test]
    public function it_tells_a_field_keyed_display_name_apart_from_the_name_shown(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        $field->key = 'display_name';
        $field->label = 'Display name';
        $field->save();
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $this->valueFor($jim, $field, 'Big Tuna');
        $theirs = $this->valueFor($james, $field, 'Jimothy');

        new MergeContacts(user: $user, kept: $jim, absorbed: $james, resolutions: [
            ['field_key' => 'display_name', 'row' => null, 'keep' => 'other'],
            ['field_key' => '@display_name', 'row' => null, 'keep' => 'kept'],
        ])->execute();

        $this->assertSame('Jim Halpert', $jim->refresh()->name);
        $this->assertDatabaseHas('field_values', [
            'id' => $theirs->id,
            'valuable_id' => $jim->id,
            'is_detached' => false,
        ]);
    }
}
