<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\MergeContacts;
use App\Actions\UndoContactMerge;
use App\Enums\DatePrecisionEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\ContactMergeCannotBeUndone;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\ContactNameRepresentation;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Reminder;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UndoContactMergeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_the_absorbed_contact_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertNotSoftDeleted($james);
        $this->assertDatabaseHas('contact_merges', [
            'id' => $merge->id,
            'undone_by_user_id' => $user->id,
        ]);
        $this->assertNotNull($merge->fresh()->undone_at);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactMergeUndone);
    }

    #[Test]
    public function it_gives_a_moved_value_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $value = $this->valueFor($james, $field, 'james@dundermifflin.com');

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
        $this->assertEquals($jim->id, $value->fresh()->valuable_id);

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'valuable_id' => $james->id,
            'is_detached' => false,
        ]);
    }

    #[Test]
    public function it_reattaches_the_value_the_survivor_gave_up(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $mine = $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $theirs = $this->valueFor($james, $field, 'james@dundermifflin.com');

        $merge = new MergeContacts(
            user: $user,
            kept: $jim,
            absorbed: $james,
            resolutions: [['field_key' => $field->key, 'row' => null, 'keep' => 'other']],
        )->execute();

        $this->assertTrue($mine->fresh()->is_detached);

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertDatabaseHas('field_values', ['id' => $mine->id, 'valuable_id' => $jim->id, 'is_detached' => false]);
        $this->assertDatabaseHas('field_values', ['id' => $theirs->id, 'valuable_id' => $james->id]);
    }

    #[Test]
    public function it_wakes_up_the_reminder_on_the_value_the_survivor_gave_up(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        [$jim, $james] = $this->twoContactsOf($user, $version);
        $reminder = Reminder::factory()->create(['field_value_id' => $this->valueFor($jim, $field, 'jim@dundermifflin.com')->id]);
        $this->valueFor($james, $field, 'james@dundermifflin.com');

        $merge = new MergeContacts(
            user: $user,
            kept: $jim,
            absorbed: $james,
            resolutions: [['field_key' => $field->key, 'row' => null, 'keep' => 'other']],
        )->execute();

        $this->assertFalse($reminder->fresh()->is_active);

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_active' => true, 'inactive_reason' => null]);
    }

    #[Test]
    public function it_reattaches_a_value_that_had_been_set_aside(): void
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

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
        $this->assertTrue($value->fresh()->is_detached);

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'valuable_id' => $james->id,
            'is_detached' => false,
        ]);
    }

    #[Test]
    public function it_gives_the_reminders_and_the_share_links_back(): void
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

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'remindable_id' => $james->id]);
        $this->assertDatabaseHas('share_links', ['id' => $link->id, 'shareable_id' => $james->id]);
    }

    #[Test]
    public function it_points_other_records_back_at_the_absorbed_contact(): void
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

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertDatabaseHas('field_values', ['id' => $pointer->id, 'related_contact_id' => $james->id]);
    }

    #[Test]
    public function it_puts_the_old_photo_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $file = File::factory()->create(['account_id' => $user->account_id, 'vault_id' => $james->vault_id]);
        $james->avatar_file_id = $file->id;
        $james->save();

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
        $this->assertEquals($file->id, $jim->fresh()->avatar_file_id);

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertNull($jim->fresh()->avatar_file_id);
    }

    #[Test]
    public function it_makes_the_survivor_partial_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $jim->is_partial = true;
        $jim->save();

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
        $this->assertFalse($jim->fresh()->is_partial);

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertTrue($jim->fresh()->is_partial);
    }

    #[Test]
    public function it_refuses_a_merge_that_was_already_undone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->expectException(ContactMergeCannotBeUndone::class);

        new UndoContactMerge(user: $user, merge: $merge->fresh())->execute();
    }

    #[Test]
    public function it_refuses_when_the_absorbed_contact_is_gone_for_good(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        Contact::withTrashed()->whereKey($james->id)->forceDelete();

        $this->expectException(ContactMergeCannotBeUndone::class);

        new UndoContactMerge(user: $user, merge: $merge->fresh())->execute();
    }

    #[Test]
    public function it_applies_nothing_when_it_refuses(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $undoneAt = $merge->fresh()->undone_at;

        try {
            new UndoContactMerge(user: $user, merge: $merge->fresh())->execute();
        } catch (ContactMergeCannotBeUndone) {
        }

        $this->assertEquals($undoneAt->toIso8601String(), $merge->fresh()->undone_at->toIso8601String());
    }

    #[Test]
    public function it_refuses_a_merge_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $stranger = ContactMerge::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UndoContactMerge(user: $user, merge: $stranger)->execute();
    }

    #[Test]
    public function it_gives_a_moved_membership_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $group = Group::factory()->create(['vault_id' => $jim->vault_id, 'name' => 'Scranton branch']);
        $membership = GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $james->id]);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
        $group->updated_at = CarbonImmutable::parse('2007-01-01');
        $group->saveQuietly();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertDatabaseHas('group_members', ['id' => $membership->id, 'contact_id' => $james->id]);
        $this->assertTrue($group->fresh()->updated_at->isAfter('2007-01-01'));
    }

    #[Test]
    public function it_splits_a_folded_membership_back_into_two(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $group = Group::factory()->create(['vault_id' => $jim->vault_id, 'name' => 'Party planning committee']);
        $mine = GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $jim->id, 'role' => null]);
        $theirs = GroupMember::factory()->create([
            'group_id' => $group->id,
            'contact_id' => $james->id,
            'role' => 'Member',
            'joined_on' => '2006-05-01',
            'joined_precision' => DatePrecisionEnum::Month,
        ]);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $mine->refresh();
        $this->assertNull($mine->joined_on);
        $this->assertEquals(DatePrecisionEnum::Unknown, $mine->joined_precision);
        $this->assertNull($mine->role);
        $this->assertNotSoftDeleted($theirs);
        $this->assertDatabaseHas('group_members', ['id' => $theirs->id, 'contact_id' => $james->id, 'role' => 'Member']);
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
    public function it_sends_both_contacts_to_search_again(): void
    {
        Queue::fake();
        $engine = $this->useRecordingSearchEngine();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
        $engine->forget();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertTrue($engine->wasSent($jim));
        $this->assertTrue($engine->wasSent($james));
    }

    #[Test]
    public function it_gives_back_every_way_of_writing_the_name_and_the_one_shown(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'structure_version_id' => $version->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $james = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'structure_version_id' => $version->id, 'first_name' => 'James', 'last_name' => 'Halpert']);
        $jamesShown = $james->display_name_representation_id;
        $jimShown = $jim->display_name_representation_id;

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james, resolutions: [
            ['field_key' => '@display_name', 'row' => null, 'keep' => 'other'],
        ])->execute();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertSame($jimShown, $jim->refresh()->display_name_representation_id);
        $this->assertSame('Jim Halpert', $jim->name);
        $this->assertSame(1, $jim->nameRepresentations()->count());
        $this->assertSame($jamesShown, $james->refresh()->display_name_representation_id);
        $this->assertSame(1, $james->nameRepresentations()->count());
        $this->assertSame('James Halpert', $james->name);
    }

    #[Test]
    public function it_shows_what_is_left_when_the_name_shown_before_the_merge_was_removed_since(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $jimShown = $jim->displayNameRepresentation;
        $jamesShown = $james->display_name_representation_id;

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james, resolutions: [
            ['field_key' => '@display_name', 'row' => null, 'keep' => 'other'],
        ])->execute();
        $jimShown->delete();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertNull($jim->refresh()->display_name_representation_id);
        $this->assertSame(0, $jim->nameRepresentations()->count());
        $this->assertSame($jamesShown, $james->refresh()->display_name_representation_id);
        $this->assertSame('James Halpert', $james->name);
    }

    #[Test]
    public function it_never_falls_back_on_a_pronunciation_when_the_name_shown_before_the_merge_was_removed_since(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $jimShown = $jim->displayNameRepresentation;
        ContactNameRepresentation::factory()->phonetic()->create(['contact_id' => $jim->id, 'given_name' => 'Jim', 'family_name' => 'Halpert', 'position' => 0]);
        $written = ContactNameRepresentation::factory()->create(['contact_id' => $jim->id, 'formatted_name' => 'Big Tuna', 'position' => 5]);

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james, resolutions: [
            ['field_key' => '@display_name', 'row' => null, 'keep' => 'other'],
        ])->execute();
        $jimShown->delete();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertSame($written->id, $jim->refresh()->display_name_representation_id);
    }

    #[Test]
    public function it_points_the_survivor_back_at_its_own_name_when_it_chose_one_that_came_with_the_merge(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $jimShown = $jim->display_name_representation_id;
        $jamesShown = $james->display_name_representation_id;

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();
        $jim->refresh();
        $jim->display_name_representation_id = $jamesShown;
        $jim->save();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $this->assertSame($jimShown, $jim->refresh()->display_name_representation_id);
        $this->assertSame('Jim Halpert', $jim->name);
        $this->assertSame($jamesShown, $james->refresh()->display_name_representation_id);
        $this->assertSame('James Halpert', $james->name);
    }

    #[Test]
    public function it_shows_the_absorbed_contacts_remaining_name_when_the_one_it_showed_was_removed_since(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);
        $twin = ContactNameRepresentation::factory()->create(['contact_id' => $james->id, 'given_name' => 'Jim', 'family_name' => 'Halpert']);
        $moved = $james->displayNameRepresentation;

        $merge = new MergeContacts(user: $user, kept: $jim, absorbed: $james, resolutions: [
            ['field_key' => '@display_name', 'row' => null, 'keep' => 'other'],
        ])->execute();
        $this->assertSame($james->id, $twin->refresh()->contact_id);
        $moved->refresh()->delete();

        new UndoContactMerge(user: $user, merge: $merge)->execute();

        $james->refresh();
        $this->assertSame($twin->id, $james->display_name_representation_id);
        $this->assertSame('Jim', $james->first_name);
        $this->assertSame('Jim Halpert', $james->name);
    }
}
