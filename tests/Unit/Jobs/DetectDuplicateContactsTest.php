<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\FieldTypeEnum;
use App\Jobs\DetectDuplicateContacts;
use App\Models\Contact;
use App\Models\ContactDuplicateCandidate;
use App\Models\ContactNameRepresentation;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\SectionVersion;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetectDuplicateContactsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_proposes_two_contacts_whose_names_resemble_each_other(): void
    {
        $vault = Vault::factory()->create();
        $jim = $this->contactIn($vault, 'Jim', 'Halpert');
        $jimmy = $this->contactIn($vault, 'Jimmy', 'Halpert');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseHas('contact_duplicate_candidates', [
            'vault_id' => $vault->id,
            'contact_id' => min($jim->id, $jimmy->id),
            'other_contact_id' => max($jim->id, $jimmy->id),
        ]);
    }

    #[Test]
    public function it_proposes_a_pair_whose_given_names_differ_by_a_typing_mistake(): void
    {
        $vault = Vault::factory()->create();
        $this->contactIn($vault, 'Michael', 'Scott');
        $this->contactIn($vault, 'Micheal', 'Scott');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 1);
    }

    #[Test]
    public function it_leaves_a_nickname_that_is_not_a_spelling_of_the_name_to_the_other_signal(): void
    {
        $vault = Vault::factory()->create();
        $this->contactIn($vault, 'Jim', 'Halpert');
        $this->contactIn($vault, 'James', 'Halpert');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 0);
    }

    #[Test]
    public function it_proposes_two_contacts_left_with_nothing_but_the_same_family_name(): void
    {
        $vault = Vault::factory()->create();
        $this->contactIn($vault, null, 'Halpert');
        $this->contactIn($vault, null, 'Halpert');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 1);
    }

    #[Test]
    public function it_proposes_nothing_for_two_plainly_different_people(): void
    {
        $vault = Vault::factory()->create();
        $this->contactIn($vault, 'Jim', 'Halpert');
        $this->contactIn($vault, 'Dwight', 'Schrute');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 0);
    }

    #[Test]
    public function it_proposes_nothing_for_two_siblings_who_share_only_a_family_name(): void
    {
        $vault = Vault::factory()->create();
        $this->contactIn($vault, 'Jim', 'Halpert');
        $this->contactIn($vault, 'Larissa', 'Halpert');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 0);
    }

    #[Test]
    public function it_proposes_a_pair_sharing_an_identifying_value_even_when_the_names_differ(): void
    {
        $vault = Vault::factory()->create();
        $field = $this->identifyingField($vault);
        $jim = $this->contactIn($vault, 'Jim', 'Halpert');
        $jimmy = $this->contactIn($vault, 'Jimmy', 'H');

        $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $this->valueFor($jimmy, $field, 'jim@dundermifflin.com');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $candidate = ContactDuplicateCandidate::query()->firstOrFail();

        $this->assertEquals(95, $candidate->score);
        $this->assertEquals('field', $candidate->reasons[0]['kind']);
        $this->assertEquals('jim@dundermifflin.com', $candidate->reasons[0]['value']);
    }

    #[Test]
    public function it_proposes_a_pair_sharing_an_address_under_two_different_labels(): void
    {
        $vault = Vault::factory()->create();
        $list = ValueList::factory()->create(['account_id' => $vault->account_id]);
        $personal = ValueListItem::factory()->create(['account_id' => $vault->account_id, 'value_list_id' => $list->id, 'label' => 'Personal']);
        $work = ValueListItem::factory()->create(['account_id' => $vault->account_id, 'value_list_id' => $list->id, 'label' => 'Work']);
        $field = $this->identifyingField($vault);
        $field->value_list_id = $list->id;
        $field->save();

        $jim = $this->contactIn($vault, 'Jim', 'Halpert');
        $jimmy = $this->contactIn($vault, 'Jimmy', 'H');

        $this->valueFor($jim, $field, 'jim@dundermifflin.com')->forceFill(['value_list_item_id' => $personal->id])->save();
        $this->valueFor($jimmy, $field, 'jim@dundermifflin.com')->forceFill(['value_list_item_id' => $work->id])->save();

        new DetectDuplicateContacts(subject: $vault)->handle();

        $candidate = ContactDuplicateCandidate::query()->firstOrFail();

        $this->assertEquals('field', $candidate->reasons[0]['kind']);
        $this->assertEquals('jim@dundermifflin.com', $candidate->reasons[0]['value']);
    }

    #[Test]
    public function it_ignores_a_value_shared_through_a_field_that_does_not_identify_anybody(): void
    {
        $vault = Vault::factory()->create();
        $field = $this->identifyingField($vault, identifying: false);
        $jim = $this->contactIn($vault, 'Jim', 'Halpert');
        $dwight = $this->contactIn($vault, 'Dwight', 'Schrute');

        $this->valueFor($jim, $field, 'Scranton');
        $this->valueFor($dwight, $field, 'Scranton');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 0);
    }

    #[Test]
    public function it_ignores_a_value_that_was_set_aside(): void
    {
        $vault = Vault::factory()->create();
        $field = $this->identifyingField($vault);
        $jim = $this->contactIn($vault, 'Jim', 'Halpert');
        $jimmy = $this->contactIn($vault, 'Jimmy', 'H');

        $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $this->valueFor($jimmy, $field, 'jim@dundermifflin.com', detached: true);

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 0);
    }

    #[Test]
    public function it_writes_one_row_for_a_pair_found_by_both_signals(): void
    {
        $vault = Vault::factory()->create();
        $field = $this->identifyingField($vault);
        $jim = $this->contactIn($vault, 'Jim', 'Halpert');
        $jimmy = $this->contactIn($vault, 'Jimmy', 'Halpert');

        $this->valueFor($jim, $field, 'jim@dundermifflin.com');
        $this->valueFor($jimmy, $field, 'jim@dundermifflin.com');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 1);
        $this->assertCount(2, ContactDuplicateCandidate::query()->firstOrFail()->reasons);
    }

    #[Test]
    public function it_never_proposes_a_contact_as_its_own_duplicate(): void
    {
        $vault = Vault::factory()->create();
        $this->contactIn($vault, 'Jim', 'Halpert');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 0);
    }

    #[Test]
    public function it_never_proposes_the_contact_representing_the_user(): void
    {
        $vault = Vault::factory()->create();
        $this->contactIn($vault, 'Jim', 'Halpert');
        Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => 'Jimmy',
            'last_name' => 'Halpert',
            'is_me' => true,
        ]);

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 0);
    }

    #[Test]
    public function it_never_pairs_contacts_of_two_vaults(): void
    {
        $vault = Vault::factory()->create();
        $elsewhere = Vault::factory()->create(['account_id' => $vault->account_id]);
        $this->contactIn($vault, 'Jim', 'Halpert');
        $this->contactIn($elsewhere, 'James', 'Halpert');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 0);
    }

    #[Test]
    public function it_looks_only_around_one_contact_when_it_is_given_one(): void
    {
        $vault = Vault::factory()->create();
        $jim = $this->contactIn($vault, 'Jim', 'Halpert');
        $this->contactIn($vault, 'Jimmy', 'Halpert');
        $this->contactIn($vault, 'Pam', 'Beesly');
        $this->contactIn($vault, 'Pamela', 'Beesly');

        new DetectDuplicateContacts(subject: $jim)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 1);
    }

    #[Test]
    public function it_keeps_a_dismissed_pair_out_of_the_way_across_a_rerun(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->contactIn($vault, 'Jim', 'Halpert');
        $this->contactIn($vault, 'Jimmy', 'Halpert');

        new DetectDuplicateContacts(subject: $vault)->handle();

        $candidate = ContactDuplicateCandidate::query()->firstOrFail();
        $candidate->dismissed_at = now();
        $candidate->dismissed_by_user_id = $user->id;
        $candidate->save();

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 1);
        $this->assertNotNull(ContactDuplicateCandidate::query()->firstOrFail()->dismissed_at);
    }

    #[Test]
    public function it_pairs_two_contacts_sharing_only_a_transliteration(): void
    {
        $vault = Vault::factory()->create();
        $hangul = $this->contactIn($vault, '민지', '김');
        $latin = $this->contactIn($vault, 'Minnie', 'Kimball');
        ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $hangul->id, 'given_name' => 'Min-ji', 'family_name' => 'Kim']);
        ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $latin->id, 'given_name' => 'Min-ji', 'family_name' => 'Kim']);

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseHas('contact_duplicate_candidates', [
            'contact_id' => min($hangul->id, $latin->id),
            'other_contact_id' => max($hangul->id, $latin->id),
        ]);
    }

    #[Test]
    public function it_only_suggests_two_contacts_sharing_a_pronunciation(): void
    {
        $vault = Vault::factory()->create();
        $one = $this->contactIn($vault, 'Ryan', 'Howard');
        $two = $this->contactIn($vault, 'Rian', 'Hauard');
        ContactNameRepresentation::factory()->phonetic('ipa')->create(['contact_id' => $one->id, 'given_name' => 'ˈraɪən', 'family_name' => 'ˈhaʊərd']);
        ContactNameRepresentation::factory()->phonetic('ipa')->create(['contact_id' => $two->id, 'given_name' => 'ˈraɪən', 'family_name' => 'ˈhaʊərd']);

        new DetectDuplicateContacts(subject: $one)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 1);
        $this->assertDatabaseCount('contacts', 2);
        $this->assertNull($one->refresh()->deleted_at);
        $this->assertNull($two->refresh()->deleted_at);
    }

    #[Test]
    public function it_drops_a_pair_that_stopped_looking_alike(): void
    {
        $vault = Vault::factory()->create();
        $this->contactIn($vault, 'Jim', 'Halpert');
        $jimmy = $this->contactIn($vault, 'Jimmy', 'Halpert');

        new DetectDuplicateContacts(subject: $vault)->handle();
        $this->assertDatabaseCount('contact_duplicate_candidates', 1);

        $jimmy->displayNameRepresentation->update(['given_name' => 'Dwight', 'family_name' => 'Schrute']);

        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 0);
    }

    #[Test]
    public function it_is_idempotent(): void
    {
        $vault = Vault::factory()->create();
        $this->contactIn($vault, 'Jim', 'Halpert');
        $this->contactIn($vault, 'Jimmy', 'Halpert');

        new DetectDuplicateContacts(subject: $vault)->handle();
        new DetectDuplicateContacts(subject: $vault)->handle();

        $this->assertDatabaseCount('contact_duplicate_candidates', 1);
    }

    #[Test]
    public function it_writes_the_match_key_from_the_family_name(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Jim', 'last_name' => 'Halpert']);

        $this->assertEquals('halpert', $contact->match_key);
    }

    #[Test]
    public function it_falls_back_to_the_only_name_part_there_is(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => null,
            'last_name' => null,
            'nickname' => 'Scranton Strangler',
        ]);

        $this->assertEquals('scranton strangler', $contact->match_key);
    }

    #[Test]
    public function it_queues_one_pass_per_subject(): void
    {
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->assertEquals(
            new DetectDuplicateContacts($vault)->uniqueId(),
            new DetectDuplicateContacts($vault)->uniqueId(),
        );

        $this->assertNotEquals(
            new DetectDuplicateContacts($vault)->uniqueId(),
            new DetectDuplicateContacts($contact)->uniqueId(),
        );

        $this->assertNotEquals(
            new DetectDuplicateContacts($vault)->uniqueId(),
            new DetectDuplicateContacts()->uniqueId(),
        );
    }

    private function contactIn(Vault $vault, ?string $first, string $last): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    private function identifyingField(Vault $vault, bool $identifying = true): Field
    {
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $vault->account_id]);

        return Field::factory()->create([
            'account_id' => $vault->account_id,
            'section_version_id' => $sectionVersion->id,
            'type' => FieldTypeEnum::Email,
            'is_identifying' => $identifying,
        ]);
    }

    private function valueFor(Contact $contact, Field $field, string $value, bool $detached = false): FieldValue
    {
        return FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => $value,
            'is_detached' => $detached,
            'detached_at' => $detached ? now() : null,
        ]);
    }
}
