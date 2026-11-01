<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\PreviewContactMerge;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotAbsorbTheMeContact;
use App\Exceptions\CannotMergeAContactWithItself;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreviewContactMergeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_both_contacts(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user);
        $jim = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $version, 'Jim', 'Halpert');

        $preview = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertEquals($jim->uuid, $preview['kept']['uuid']);
        $this->assertEquals($james->uuid, $preview['other']['uuid']);
    }

    #[Test]
    public function it_reports_a_differing_value_as_a_conflict_with_both_sides(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        $jim = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $version, 'Jim', 'Halpert');

        $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $this->valueFor($james, $field, 'james@dundermifflin.com');

        $preview = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertCount(1, $preview['conflicts']);
        $this->assertEquals($field->key, $preview['conflicts'][0]['field_key']);
        $this->assertEquals('jim@dundermifflin.com', $preview['conflicts'][0]['kept_value']);
        $this->assertEquals('james@dundermifflin.com', $preview['conflicts'][0]['other_value']);
    }

    #[Test]
    public function it_reports_no_conflict_when_only_one_side_holds_a_value(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        $jim = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $version, 'Jim', 'Halpert');

        $this->valueFor($james, $field, 'james@dundermifflin.com');

        $preview = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertSame([], $preview['conflicts']);
        $this->assertContains(['kind' => 'field_value', 'count' => 1], $preview['moves']);
    }

    #[Test]
    public function it_reports_no_conflict_when_both_sides_hold_the_same_value(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        $jim = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $version, 'Jim', 'Halpert');

        $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $this->valueFor($james, $field, 'jim@dundermifflin.com');

        $preview = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertSame([], $preview['conflicts']);
    }

    #[Test]
    public function it_reports_a_photo_on_both_sides_as_a_conflict(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user);
        $jim = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $version, 'Jim', 'Halpert');

        $jim->avatar_file_id = File::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id])->id;
        $jim->save();
        $james->avatar_file_id = File::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id])->id;
        $james->save();

        $preview = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertEquals('@avatar', $preview['conflicts'][0]['field_key']);
    }

    #[Test]
    public function it_lists_a_value_with_nowhere_to_land_as_set_aside(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mine = $this->publishedContactVersion($user);
        $theirs = $this->publishedContactVersion($user);
        $onlyTheirs = $this->fieldCarriedBy($theirs);
        $jim = $this->contactIn($vault, $mine, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $theirs, 'Jim', 'Halpert');

        $this->valueFor($james, $onlyTheirs, 'Loyalty 4471');

        $preview = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertCount(1, $preview['set_aside']);
        $this->assertEquals($onlyTheirs->key, $preview['set_aside'][0]['field_key']);
        $this->assertSame([], $preview['conflicts']);
    }

    #[Test]
    public function it_counts_the_reminders_and_share_links_that_would_move(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $absorbed = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
        ]);

        $this->reminderAbout($user, $absorbed);

        $preview = new PreviewContactMerge(user: $user, kept: $contact, absorbed: $absorbed)->execute();

        $this->assertContains(['kind' => 'reminder', 'count' => 1], $preview['moves']);
    }

    #[Test]
    public function it_counts_the_pointers_other_contacts_hold_onto_the_absorbed_one(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user);
        $jim = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $pam = $this->contactIn($vault, $version, 'Pam', 'Beesly');

        FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $pam->id,
            'related_contact_id' => $james->id,
            'value_string' => null,
        ]);

        $preview = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertContains(['kind' => 'relation_pointer', 'count' => 1], $preview['moves']);
    }

    #[Test]
    public function it_changes_its_fingerprint_when_a_value_changes(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        $jim = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $value = $this->valueFor($james, $field, 'james@dundermifflin.com');

        $before = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute()['fingerprint'];

        $value->value_string = 'jamesh@dundermifflin.com';
        $value->save();

        $after = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute()['fingerprint'];

        $this->assertNotEquals($before, $after);
    }

    #[Test]
    public function it_reports_a_row_both_contacts_hold_as_a_collision(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $jim = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $version, 'Jim', 'Halpert');

        $this->rowWithValueFor($jim, $repeater, $field, 'Scranton');
        $this->rowWithValueFor($jim, $repeater, $field, 'Stamford');
        $scranton = $this->rowWithValueFor($james, $repeater, $field, 'Scranton');
        $this->rowWithValueFor($james, $repeater, $field, 'Nashua');

        $preview = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertEquals([
            ['kind' => 'field_value_row', 'field_key' => $repeater->key, 'row' => $scranton->id],
        ], $preview['collisions']);
    }

    #[Test]
    public function it_reads_each_contact_from_the_database_once(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        $jim = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $version, 'Jim', 'Halpert');

        $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $this->valueFor($james, $field, 'james@dundermifflin.com');
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $this->rowWithValueFor($jim, $repeater, $field, 'Scranton');
        $this->rowWithValueFor($james, $repeater, $field, 'Scranton');

        DB::flushQueryLog();
        DB::enableQueryLog();

        new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $queries = array_column(DB::getRawQueryLog(), 'raw_query');

        DB::disableQueryLog();

        $this->assertSame(array_values(array_unique($queries)), $queries);
    }

    #[Test]
    public function it_reports_a_group_both_contacts_are_current_members_of_as_a_fold(): void
    {
        $user = User::factory()->create();
        $jim = Contact::factory()->create(['account_id' => $user->account_id]);
        $james = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $jim->vault_id]);
        $branch = Group::factory()->create(['vault_id' => $jim->vault_id, 'name' => 'Scranton branch']);
        $committee = Group::factory()->create(['vault_id' => $jim->vault_id, 'name' => 'Party planning committee']);
        GroupMember::factory()->create(['group_id' => $branch->id, 'contact_id' => $jim->id]);
        GroupMember::factory()->create(['group_id' => $branch->id, 'contact_id' => $james->id]);
        GroupMember::factory()->create(['group_id' => $committee->id, 'contact_id' => $james->id]);
        GroupMember::factory()->former()->create(['group_id' => $committee->id, 'contact_id' => $jim->id]);

        $preview = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertSame([['group' => $branch->uuid, 'name' => 'Scranton branch']], $preview['folds']);
        $this->assertContains(['kind' => 'group_membership', 'count' => 1], $preview['moves']);
    }

    #[Test]
    public function it_changes_its_fingerprint_when_a_membership_ends(): void
    {
        $user = User::factory()->create();
        $jim = Contact::factory()->create(['account_id' => $user->account_id]);
        $james = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $jim->vault_id]);
        $membership = GroupMember::factory()->create([
            'group_id' => Group::factory()->create(['vault_id' => $jim->vault_id])->id,
            'contact_id' => $james->id,
        ]);
        $before = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute()['fingerprint'];

        $membership->update(['left_on' => '2013-05-16']);

        $after = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute()['fingerprint'];

        $this->assertNotSame($before, $after);
    }

    #[Test]
    public function it_refuses_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $stranger = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new PreviewContactMerge(user: $user, kept: $contact, absorbed: $stranger)->execute();
    }

    #[Test]
    public function it_refuses_two_contacts_of_different_vaults(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Contact::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new PreviewContactMerge(user: $user, kept: $contact, absorbed: $elsewhere)->execute();
    }

    #[Test]
    public function it_refuses_merging_a_contact_with_itself(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(CannotMergeAContactWithItself::class);

        new PreviewContactMerge(user: $user, kept: $contact, absorbed: $contact)->execute();
    }

    #[Test]
    public function it_refuses_absorbing_the_contact_representing_the_user(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'is_me' => true,
        ]);

        $this->expectException(CannotAbsorbTheMeContact::class);

        new PreviewContactMerge(user: $user, kept: $contact, absorbed: $me)->execute();
    }

    #[Test]
    public function it_lets_the_contact_representing_the_user_absorb_another(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'is_me' => true,
        ]);

        $preview = new PreviewContactMerge(user: $user, kept: $me, absorbed: $contact)->execute();

        $this->assertEquals($me->uuid, $preview['kept']['uuid']);
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
        $version->refresh();

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

    #[Test]
    public function it_asks_which_name_to_show_when_the_two_are_shown_under_different_names(): void
    {
        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $version, 'James', 'Halpert');

        $preview = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertSame([[
            'field_key' => '@display_name',
            'field_id' => null,
            'label' => 'Name shown',
            'row' => null,
            'kept_value' => 'Jim Halpert',
            'other_value' => 'James Halpert',
        ]], $preview['conflicts']);
        $this->assertContains(['kind' => 'name_representation', 'count' => 1], $preview['moves']);
    }

    #[Test]
    public function it_gives_the_name_shown_a_key_no_field_can_take(): void
    {
        $user = User::factory()->create();
        $version = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($version);
        $field->key = 'display_name';
        $field->label = 'Display name';
        $field->save();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = $this->contactIn($vault, $version, 'Jim', 'Halpert');
        $james = $this->contactIn($vault, $version, 'James', 'Halpert');
        $this->valueFor($jim, $field, 'Big Tuna');
        $this->valueFor($james, $field, 'Jimothy');

        $preview = new PreviewContactMerge(user: $user, kept: $jim, absorbed: $james)->execute();

        $this->assertSame(['@display_name', 'display_name'], array_column($preview['conflicts'], 'field_key'));
    }
}
