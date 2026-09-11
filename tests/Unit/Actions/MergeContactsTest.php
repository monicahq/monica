<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\MergeContacts;
use App\Actions\PreviewContactMerge;
use App\Enums\SnapshotReasonEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotAbsorbTheMeContact;
use App\Exceptions\ContactChangedSinceThePreview;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\RecordSnapshot;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
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
    public function it_writes_to_the_changelog(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$jim, $james] = $this->twoContactsOf($user);

        new MergeContacts(user: $user, kept: $jim, absorbed: $james)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactsMerged);
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

    /** @return array{0: Contact, 1: Contact} */
    private function twoContactsOf(User $user, ?TemplateVersion $version = null): array
    {
        $version ??= $this->publishedContactVersion($user);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [
            $this->contactIn($vault, $version, 'Jim', 'Halpert'),
            $this->contactIn($vault, $version, 'James', 'Halpert'),
        ];
    }

    private function contactIn(Vault $vault, TemplateVersion $version, string $first, string $last): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => $version->id,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    private function publishedContactVersion(User $user): TemplateVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        return TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
    }

    private function fieldCarriedBy(TemplateVersion $version): Field
    {
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $version->account_id]);
        $field = Field::factory()->create([
            'account_id' => $version->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->status = VersionStatusEnum::Draft;
        $version->save();

        TemplateVersionSection::factory()->create([
            'account_id' => $version->account_id,
            'template_version_id' => $version->id,
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
}
