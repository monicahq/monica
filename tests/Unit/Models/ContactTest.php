<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ContactGroupingEnum;
use App\Enums\ContactNameKindEnum;
use App\Enums\ContactSortEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\NameOrderEnum;
use App\Enums\RelationshipTypeEnum;
use App\Enums\SortDirectionEnum;
use App\Helpers\LocaleCollation;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Reminder;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\ValueListItem;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_holds_the_private_links_opened_onto_its_page(): void
    {
        $contact = Contact::factory()->create();
        $shareLink = ShareLink::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'shareable_type' => Contact::class,
            'shareable_id' => $contact->id,
        ]);

        $this->assertTrue($contact->shareLinks()->exists());
        $this->assertTrue($shareLink->is($contact->shareLinks->first()));
    }

    #[Test]
    public function it_gets_an_unguessable_identifier_and_keeps_a_number_for_a_key(): void
    {
        $contact = Contact::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $contact->uuid,
        );
        $this->assertIsInt($contact->id);
        $this->assertEquals('slug', $contact->getRouteKeyName());
        $this->assertEquals($contact->slug, $contact->getRouteKey());
    }

    #[Test]
    public function it_writes_the_string_the_lists_sort_on_whenever_it_is_saved(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'Michael',
            'middle_name' => 'Gary',
            'last_name' => 'Scott',
        ]);

        $this->assertEquals('scott michael gary', $contact->sort_name);

        $contact->displayNameRepresentation->update(['family_name' => 'Scarn']);
        $contact->refresh();

        $this->assertEquals('scarn michael gary', $contact->fresh()->sort_name);
    }

    #[Test]
    public function it_sorts_a_contact_who_only_has_a_nickname_on_that_nickname(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => null,
            'middle_name' => null,
            'last_name' => null,
            'nickname' => 'Prison Mike',
        ]);

        $this->assertEquals('prison mike', $contact->sort_name);
    }

    #[Test]
    public function it_accepts_a_contact_with_no_part_of_a_name_at_all(): void
    {
        $contact = Contact::factory()->create([
            'prefix' => 'Dr',
            'first_name' => null,
            'middle_name' => null,
            'last_name' => null,
            'nickname' => null,
            'maiden_name' => null,
        ]);

        $this->assertTrue($contact->isNameless());
        $this->assertEquals('', $contact->sort_name);
        $this->assertEquals('', $contact->search_key);
    }

    #[Test]
    public function it_shows_a_nameless_contact_through_the_fallback_without_storing_it(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => null,
            'middle_name' => null,
            'last_name' => null,
            'nickname' => null,
            'maiden_name' => null,
        ]);

        $this->assertEquals(__('Unnamed contact'), $contact->name);
        $this->assertEquals('?', $contact->initials);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'first_name' => null, 'last_name' => null]);
    }

    #[Test]
    public function two_nameless_contacts_stay_separate_people(): void
    {
        $vault = Vault::factory()->create();
        $first = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => null, 'last_name' => null, 'nickname' => null, 'maiden_name' => null]);
        $second = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => null, 'last_name' => null, 'nickname' => null, 'maiden_name' => null]);

        $this->assertNotEquals($first->id, $second->id);
        $this->assertNotEquals($first->slug, $second->slug);
    }

    #[Test]
    public function it_writes_the_name_in_the_order_the_contact_reads_in(): void
    {
        $contact = Contact::factory()->make([
            'first_name' => 'Dwight',
            'middle_name' => 'Kurt',
            'last_name' => 'Schrute',
            'name_order' => NameOrderEnum::FamilyFirst,
        ]);

        $this->assertEquals('Schrute Dwight Kurt', $contact->name);
        $this->assertEquals('Dwight Kurt Schrute', $contact->nameInOrder(NameOrderEnum::GivenFirst));
    }

    #[Test]
    public function it_writes_the_given_name_first_when_the_contact_asks_for_no_order(): void
    {
        $contact = Contact::factory()->make([
            'first_name' => 'Pam',
            'middle_name' => null,
            'last_name' => 'Beesly',
            'name_order' => null,
        ]);

        $this->assertEquals('Pam Beesly', $contact->name);
    }

    #[Test]
    public function it_falls_back_to_the_nickname_when_there_is_nothing_else_to_write(): void
    {
        $contact = Contact::factory()->make([
            'first_name' => null,
            'middle_name' => null,
            'last_name' => null,
            'nickname' => 'Prison Mike',
        ]);

        $this->assertEquals('Prison Mike', $contact->name);
    }

    #[Test]
    public function it_builds_the_initials_drawn_on_the_avatar(): void
    {
        $contact = Contact::factory()->make([
            'first_name' => 'Dwight',
            'middle_name' => 'Kurt',
            'last_name' => 'Schrute',
            'name_order' => NameOrderEnum::FamilyFirst,
        ]);

        $this->assertEquals('DS', $contact->initials);
    }

    #[Test]
    public function it_builds_one_initial_when_there_is_only_one_word_to_take_it_from(): void
    {
        $contact = Contact::factory()->make([
            'first_name' => null,
            'middle_name' => null,
            'last_name' => null,
            'nickname' => 'Scarn',
        ]);

        $this->assertEquals('S', $contact->initials);
    }

    #[Test]
    public function it_lists_only_the_contacts_that_are_neither_archived_nor_partial(): void
    {
        $vault = Vault::factory()->create();
        $listed = Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Halpert']);
        Contact::factory()->archived()->create(['vault_id' => $vault->id, 'last_name' => 'Scott']);
        Contact::factory()->partial()->create(['vault_id' => $vault->id, 'first_name' => 'Jack']);

        $contacts = Contact::query()->listed()->get();

        $this->assertCount(1, $contacts);
        $this->assertTrue($listed->is($contacts->first()));
    }

    #[Test]
    public function it_groups_everyone_who_is_neither_archived_nor_partial(): void
    {
        $vault = Vault::factory()->create();
        $beesly = Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Beesly']);
        $halpert = Contact::factory()->favourite()->create(['vault_id' => $vault->id, 'last_name' => 'Halpert']);
        Contact::factory()->archived()->create(['vault_id' => $vault->id, 'last_name' => 'Scott']);
        Contact::factory()->partial()->create(['vault_id' => $vault->id, 'first_name' => 'Jack']);

        $contacts = Contact::query()->inGrouping(ContactGroupingEnum::Everyone)->get();

        $this->assertEqualsCanonicalizing([$beesly->id, $halpert->id], $contacts->pluck('id')->all());
    }

    #[Test]
    public function it_groups_only_the_favourites(): void
    {
        $vault = Vault::factory()->create();
        $halpert = Contact::factory()->favourite()->create(['vault_id' => $vault->id, 'last_name' => 'Halpert']);
        Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Beesly']);
        Contact::factory()->favourite()->archived()->create(['vault_id' => $vault->id, 'last_name' => 'Scott']);

        $contacts = Contact::query()->inGrouping(ContactGroupingEnum::Favourites)->get();

        $this->assertCount(1, $contacts);
        $this->assertTrue($halpert->is($contacts->first()));
    }

    #[Test]
    public function it_groups_only_the_people_who_are_a_name_and_nothing_else(): void
    {
        $vault = Vault::factory()->create();
        $jack = Contact::factory()->partial()->create(['vault_id' => $vault->id, 'first_name' => 'Jack']);
        Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Beesly']);
        Contact::factory()->archived()->create(['vault_id' => $vault->id, 'last_name' => 'Scott']);

        $contacts = Contact::query()->inGrouping(ContactGroupingEnum::NamesOnly)->get();

        $this->assertCount(1, $contacts);
        $this->assertTrue($jack->is($contacts->first()));
    }

    #[Test]
    public function it_leaves_the_archived_out_of_every_grouping(): void
    {
        $vault = Vault::factory()->create();
        Contact::factory()->archived()->favourite()->create(['vault_id' => $vault->id, 'last_name' => 'Scott']);
        Contact::factory()->archived()->partial()->create(['vault_id' => $vault->id, 'first_name' => 'Jack']);

        foreach (ContactGroupingEnum::cases() as $grouping) {
            $this->assertCount(0, Contact::query()->inGrouping($grouping)->get(), $grouping->value);
        }
    }

    #[Test]
    public function it_matches_a_search_term_against_the_name_ignoring_what_the_language_calls_a_minor_difference(): void
    {
        $vault = Vault::factory()->create();
        $halpert = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Élodie', 'last_name' => 'Halpert']);
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Michael', 'last_name' => 'Scott']);

        $contacts = Contact::query()->matchingSearch('elodie')->get();

        $this->assertCount(1, $contacts);
        $this->assertTrue($halpert->is($contacts->first()));
    }

    #[Test]
    public function it_matches_a_search_term_against_the_nickname_as_it_was_typed(): void
    {
        $vault = Vault::factory()->create();
        $schrute = Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Schrute', 'nickname' => 'Beet Boy']);
        Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Scott', 'nickname' => null]);

        $contacts = Contact::query()->matchingSearch('BEET')->get();

        $this->assertCount(1, $contacts);
        $this->assertTrue($schrute->is($contacts->first()));
    }

    #[Test]
    public function it_matches_everybody_when_the_search_term_is_blank(): void
    {
        $vault = Vault::factory()->create();
        Contact::factory()->count(2)->create(['vault_id' => $vault->id]);

        $this->assertCount(2, Contact::query()->matchingSearch('   ')->get());
    }

    #[Test]
    public function it_looks_up_the_other_ways_a_name_is_written_by_contact_rather_than_reading_every_one(): void
    {
        $this->assertTrue(Schema::hasIndex('contact_name_representations', ['contact_id', 'search_key']));
    }

    #[Test]
    public function it_sorts_by_a_column_in_the_direction_asked_for(): void
    {
        $vault = Vault::factory()->create();
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Michael', 'last_name' => 'Scott']);

        $descending = Contact::query()->sortedBy(ContactSortEnum::Name, SortDirectionEnum::Descending)->pluck('last_name')->all();

        $this->assertEquals(['Scott', 'Beesly'], $descending);
    }

    #[Test]
    public function it_sorts_the_contacts_a_column_says_nothing_about_to_the_end_in_both_directions(): void
    {
        $vault = Vault::factory()->create();
        Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Beesly', 'last_interaction_at' => null]);
        Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Schrute', 'last_interaction_at' => CarbonImmutable::parse('2026-01-02')]);
        Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Scott', 'last_interaction_at' => CarbonImmutable::parse('2026-01-01')]);

        $ascending = Contact::query()->sortedBy(ContactSortEnum::LastSpoke, SortDirectionEnum::Ascending)->pluck('last_name')->all();
        $descending = Contact::query()->sortedBy(ContactSortEnum::LastSpoke, SortDirectionEnum::Descending)->pluck('last_name')->all();

        $this->assertEquals(['Scott', 'Schrute', 'Beesly'], $ascending);
        $this->assertEquals(['Schrute', 'Scott', 'Beesly'], $descending);
    }

    #[Test]
    public function it_ends_every_sort_on_the_identifier_so_a_page_boundary_cannot_move(): void
    {
        $vault = Vault::factory()->create();
        $moment = CarbonImmutable::parse('2026-01-01 09:00:00');
        $first = Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Scott', 'created_at' => $moment]);
        $second = Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Scott', 'created_at' => $moment]);

        $order = Contact::query()->sortedBy(ContactSortEnum::Added, SortDirectionEnum::Ascending)->pluck('id')->all();

        $this->assertEquals([$first->id, $second->id], $order);
    }

    #[Test]
    public function it_is_deleted_softly(): void
    {
        $contact = Contact::factory()->create(['last_name' => 'Schrute']);

        $contact->delete();

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
        $this->assertEquals(0, Contact::query()->count());
        $this->assertEquals(1, Contact::query()->withTrashed()->count());
    }

    #[Test]
    public function it_derives_the_two_ordering_keys_when_it_saves(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'Élodie',
            'last_name' => 'Bernard',
            'middle_name' => null,
        ]);

        $this->assertSame(
            LocaleCollation::sortKey($contact->sort_name, $contact->account->locale()),
            $contact->sort_key,
        );
        $this->assertSame(
            LocaleCollation::foldForSearch($contact->sort_name, $contact->account->locale()),
            $contact->search_key,
        );
    }

    #[Test]
    public function it_rebuilds_the_two_ordering_keys_when_the_name_changes(): void
    {
        $contact = Contact::factory()->create(['last_name' => 'Scott']);
        $before = $contact->sort_key;

        $contact->displayNameRepresentation->update(['family_name' => 'Scarn']);
        $contact->refresh();

        $fresh = $contact->fresh();

        $this->assertNotSame($before, $fresh->sort_key);
        $this->assertStringStartsWith('scarn', $fresh->sort_name);
        $this->assertSame(
            LocaleCollation::sortKey($fresh->sort_name, $contact->account->locale()),
            $fresh->sort_key,
        );
    }

    #[Test]
    public function it_builds_the_keys_for_the_language_of_the_account_and_not_the_request(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id, 'locale' => 'tr_TR']);
        $contact = Contact::factory()->create(['account_id' => $account->id, 'last_name' => 'Irmak']);

        $this->app->setLocale('fr_FR');
        $contact->save();

        $this->assertSame($user->locale, $account->locale());
        $this->assertSame(
            LocaleCollation::foldForSearch($contact->sort_name, 'tr_TR'),
            $contact->fresh()->search_key,
        );
    }

    #[Test]
    public function it_does_not_let_the_two_ordering_keys_be_mass_assigned(): void
    {
        $contact = Contact::factory()->create();

        $this->assertNotContains('sort_key', $contact->getFillable());
        $this->assertNotContains('search_key', $contact->getFillable());
    }

    #[Test]
    public function it_knows_every_reminder_rule_about_it_including_the_quiet_ones(): void
    {
        $reminder = Reminder::factory()->create();
        $quiet = Reminder::factory()->inactive()->create([
            'remindable_type' => Contact::class,
            'remindable_id' => $reminder->remindable_id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        $this->assertEqualsCanonicalizing(
            [$reminder->id, $quiet->id],
            $reminder->remindable->reminders()->pluck('id')->all(),
        );
    }

    #[Test]
    public function it_carries_no_card_until_a_device_writes_one(): void
    {
        $contact = Contact::factory()->create();

        $this->assertNull($contact->dav_uri);
        $this->assertNull($contact->vcard);
    }

    #[Test]
    public function it_keeps_the_card_a_device_sent_and_the_name_that_device_chose(): void
    {
        $card = "BEGIN:VCARD\r\nVERSION:4.0\r\nFN:Dwight Schrute\r\nEND:VCARD\r\n";

        $contact = Contact::factory()->create([
            'dav_uri' => 'dwight.vcf',
            'vcard' => $card,
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'dav_uri' => 'dwight.vcf',
        ]);
        $this->assertEquals($card, $contact->refresh()->vcard);
    }

    #[Test]
    public function it_gets_a_readable_address_built_from_the_whole_displayed_name(): void
    {
        $contact = Contact::factory()->create([
            'prefix' => 'Dr',
            'first_name' => 'Jim',
            'middle_name' => 'Michael',
            'last_name' => 'Halpert',
            'suffix' => 'Jr',
        ]);

        $this->assertSame('dr-jim-michael-halpert-jr', $contact->slug);
        $this->assertDatabaseHas('contact_slug_reservations', [
            'vault_id' => $contact->vault_id,
            'contact_id' => $contact->id,
            'slug' => 'dr-jim-michael-halpert-jr',
        ]);
    }

    #[Test]
    public function it_puts_the_maiden_name_in_the_address_too(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'Pam',
            'last_name' => 'Halpert',
            'maiden_name' => 'Beesly',
        ]);

        $this->assertSame('pam-halpert-beesly', $contact->slug);
    }

    #[Test]
    public function it_builds_the_address_in_the_order_the_name_reads_in(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => '太郎',
            'last_name' => '田中',
            'name_order' => NameOrderEnum::FamilyFirst,
        ]);

        $this->assertSame('田中-太郎', $contact->slug);
    }

    #[Test]
    public function it_numbers_a_second_contact_of_the_same_name_in_one_vault(): void
    {
        $vault = Vault::factory()->create();

        $first = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $second = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);

        $this->assertSame('jim-halpert', $first->slug);
        $this->assertSame('jim-halpert-2', $second->slug);
    }

    #[Test]
    public function it_lets_another_vault_hold_the_same_readable_address(): void
    {
        $account = Account::factory()->create();
        $here = Vault::factory()->create(['account_id' => $account->id]);
        $there = Vault::factory()->create(['account_id' => $account->id]);

        $mine = Contact::factory()->create(['vault_id' => $here->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $theirs = Contact::factory()->create(['vault_id' => $there->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);

        $this->assertSame('jim-halpert', $mine->slug);
        $this->assertSame('jim-halpert', $theirs->slug);
    }

    #[Test]
    public function it_refuses_a_name_a_route_already_fixes(): void
    {
        $vault = Vault::factory()->create();

        $new = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'New', 'last_name' => null]);
        $favourites = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Favourites', 'last_name' => null]);

        $this->assertSame('new-2', $new->slug);
        $this->assertSame('favourites-2', $favourites->slug);
    }

    #[Test]
    public function a_partial_contact_known_by_one_name_still_gets_an_address(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => null,
            'is_partial' => true,
        ]);

        $this->assertSame('dwight', $contact->slug);
    }

    #[Test]
    public function the_me_contact_gets_an_address_like_anybody_else(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'is_me' => true,
        ]);

        $this->assertSame('michael-scott', $contact->slug);
    }

    #[Test]
    public function a_contact_named_with_digits_resolves_to_itself_rather_than_to_the_row_of_that_number(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $decoy = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Decoy', 'last_name' => null]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => (string) $decoy->id, 'last_name' => null]);

        $this->actingAs($user);

        $this->assertTrue($contact->is(new Contact()->resolveRouteBinding((string) $decoy->id)));
    }

    #[Test]
    public function it_is_reached_by_its_readable_address_and_by_its_identifier(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);

        $this->actingAs($user);

        $this->assertTrue($contact->is(new Contact()->resolveRouteBinding('jim-halpert')));
        $this->assertTrue($contact->is(new Contact()->resolveRouteBinding($contact->uuid)));
    }

    #[Test]
    public function correcting_a_name_rebuilds_the_readable_address(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);

        $contact->displayNameRepresentation->update(['family_name' => 'Halpert']);
        $contact->refresh();

        $this->assertSame('pam-halpert', $contact->fresh()->slug);
    }

    #[Test]
    public function a_change_to_any_part_of_the_name_rebuilds_it(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);

        $contact->displayNameRepresentation->update(['additional_name' => 'Kurt']);
        $contact->refresh();

        $this->assertSame('dwight-kurt-schrute', $contact->fresh()->slug);
    }

    #[Test]
    public function a_readable_address_a_contact_has_released_is_refused_to_everybody_later(): void
    {
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);

        $contact->displayNameRepresentation->update(['family_name' => 'Halpert']);
        $contact->refresh();

        $namesake = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);

        $this->assertSame('pam-halpert', $contact->fresh()->slug);
        $this->assertSame('pam-beesly-2', $namesake->slug);
    }

    #[Test]
    public function a_save_that_touches_no_name_leaves_the_readable_address_alone(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Jim', 'last_name' => 'Halpert']);

        $contact->is_favourite = true;
        $contact->save();

        $this->assertSame('jim-halpert', $contact->fresh()->slug);
    }

    #[Test]
    public function a_contact_renamed_back_takes_the_readable_address_they_held_before(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);

        $contact->displayNameRepresentation->update(['family_name' => 'Halpert']);
        $contact->refresh();

        $contact->displayNameRepresentation->update(['family_name' => 'Beesly']);
        $contact->refresh();

        $this->assertSame('pam-beesly', $contact->fresh()->slug);
    }

    #[Test]
    public function it_reads_the_children_of_a_contact(): void
    {
        $vault = Vault::factory()->create();
        $michael = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id, 'last_name' => 'Scott']);

        Relationship::factory()->parentage()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $michael->id,
            'contact_b_id' => $child->id,
        ]);

        $this->assertSame([$child->id], Contact::query()->childrenOf($michael)->pluck('id')->all());
    }

    #[Test]
    public function it_does_not_read_a_parent_as_a_child(): void
    {
        $vault = Vault::factory()->create();
        $parent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Relationship::factory()->parentage()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);

        $this->assertSame([], Contact::query()->childrenOf($child)->pluck('id')->all());
    }

    #[Test]
    public function it_keeps_a_child_whose_relationship_ended(): void
    {
        $vault = Vault::factory()->create();
        $parent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Relationship::factory()->parentage()->ended()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);

        $this->assertSame([$child->id], Contact::query()->childrenOf($parent)->pluck('id')->all());
    }

    #[Test]
    public function it_keeps_a_child_who_is_partial_or_archived_and_drops_a_deleted_one(): void
    {
        $vault = Vault::factory()->create();
        $parent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $partial = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id, 'is_partial' => true]);
        $archived = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id, 'is_archived' => true]);
        $gone = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        foreach ([$partial, $archived, $gone] as $child) {
            Relationship::factory()->parentage()->create([
                'vault_id' => $vault->id,
                'account_id' => $vault->account_id,
                'contact_a_id' => $parent->id,
                'contact_b_id' => $child->id,
            ]);
        }

        $gone->delete();

        $found = Contact::query()->childrenOf($parent)->pluck('id')->all();

        $this->assertContains($partial->id, $found);
        $this->assertContains($archived->id, $found);
        $this->assertNotContains($gone->id, $found);
    }

    #[Test]
    public function it_drops_a_child_whose_relationship_was_deleted(): void
    {
        $vault = Vault::factory()->create();
        $parent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        $relationship = Relationship::factory()->parentage()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);

        $relationship->delete();

        $this->assertSame([], Contact::query()->childrenOf($parent)->pluck('id')->all());
    }

    #[Test]
    public function it_names_a_child_once_however_many_rows_link_them(): void
    {
        $vault = Vault::factory()->create();
        $parent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Relationship::factory()->parentage()->count(3)->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);

        $this->assertSame([$child->id], Contact::query()->childrenOf($parent)->pluck('id')->all());
    }

    #[Test]
    public function it_reads_a_current_partner_from_either_side(): void
    {
        $vault = Vault::factory()->create();
        $monica = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $chandler = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $phoebe = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Relationship::factory()->romantic()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $monica->id,
            'contact_b_id' => $chandler->id,
        ]);

        Relationship::factory()->romantic()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $phoebe->id,
            'contact_b_id' => $monica->id,
        ]);

        $found = Contact::query()->currentPartnersOf($monica)->pluck('id')->all();

        $this->assertContains($chandler->id, $found);
        $this->assertContains($phoebe->id, $found);
        $this->assertCount(2, $found);
    }

    #[Test]
    public function it_drops_a_partner_whose_relationship_ended(): void
    {
        $vault = Vault::factory()->create();
        $monica = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $richard = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Relationship::factory()->romantic()->ended()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $monica->id,
            'contact_b_id' => $richard->id,
            'ended_on' => null,
        ]);

        $this->assertSame([], Contact::query()->currentPartnersOf($monica)->pluck('id')->all());
    }

    #[Test]
    public function it_counts_one_partner_when_an_ended_row_and_a_current_row_name_the_same_person(): void
    {
        $vault = Vault::factory()->create();
        $monica = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $chandler = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Relationship::factory()->romantic()->ended()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $monica->id,
            'contact_b_id' => $chandler->id,
        ]);

        Relationship::factory()->romantic()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $monica->id,
            'contact_b_id' => $chandler->id,
        ]);

        $this->assertSame([$chandler->id], Contact::query()->currentPartnersOf($monica)->pluck('id')->all());
    }

    #[Test]
    public function it_reads_no_family_from_a_type_the_user_invented(): void
    {
        $vault = Vault::factory()->create();
        $monica = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $other = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        $invented = RelationshipType::factory()->create([
            'account_id' => $vault->account_id,
            'name' => 'Spouse',
            'role_a' => 'Spouse',
            'role_b' => 'Spouse',
        ]);

        Relationship::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'relationship_type_id' => $invented->id,
            'contact_a_id' => $monica->id,
            'contact_b_id' => $other->id,
        ]);

        $this->assertSame([], Contact::query()->currentPartnersOf($monica)->pluck('id')->all());
        $this->assertSame([], Contact::query()->childrenOf($monica)->pluck('id')->all());
    }

    #[Test]
    public function it_reads_no_family_from_a_sibling_or_a_colleague(): void
    {
        $vault = Vault::factory()->create();
        $monica = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        foreach ([RelationshipTypeEnum::Sibling, RelationshipTypeEnum::Colleague, RelationshipTypeEnum::Cousin] as $case) {
            $type = RelationshipType::factory()->system($case)->create(['account_id' => $vault->account_id]);
            $other = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

            Relationship::factory()->create([
                'vault_id' => $vault->id,
                'account_id' => $vault->account_id,
                'relationship_type_id' => $type->id,
                'contact_a_id' => $monica->id,
                'contact_b_id' => $other->id,
            ]);
        }

        $this->assertSame([], Contact::query()->childrenOf($monica)->pluck('id')->all());
        $this->assertSame([], Contact::query()->currentPartnersOf($monica)->pluck('id')->all());
    }

    #[Test]
    public function it_keeps_family_written_with_a_type_since_renamed_or_withdrawn(): void
    {
        $vault = Vault::factory()->create();
        $parent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        $type = RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create(['account_id' => $vault->account_id]);

        Relationship::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);

        $type->update(['name' => 'Progenitor', 'name_translation_key' => null, 'is_active' => false]);

        $this->assertSame([$child->id], Contact::query()->childrenOf($parent)->pluck('id')->all());
    }

    #[Test]
    public function it_reads_no_family_from_another_vault_or_another_account(): void
    {
        $vault = Vault::factory()->create();
        $parent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $vault->account_id]);
        $child = Contact::factory()->create(['vault_id' => $elsewhere->id, 'account_id' => $elsewhere->account_id]);

        Relationship::factory()->parentage()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);

        $this->assertSame([], Contact::query()->childrenOf($parent)->pluck('id')->all());
    }

    #[Test]
    public function it_reads_the_parents_of_a_contact(): void
    {
        $vault = Vault::factory()->create();
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $mother = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $father = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        foreach ([$mother, $father] as $parent) {
            Relationship::factory()->parentage()->create([
                'vault_id' => $vault->id,
                'account_id' => $vault->account_id,
                'contact_a_id' => $parent->id,
                'contact_b_id' => $child->id,
            ]);
        }

        $found = Contact::query()->parentsOf($child)->pluck('id')->all();

        $this->assertContains($mother->id, $found);
        $this->assertContains($father->id, $found);
        $this->assertCount(2, $found);
    }

    #[Test]
    public function it_does_not_read_a_child_as_a_parent(): void
    {
        $vault = Vault::factory()->create();
        $parent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Relationship::factory()->parentage()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);

        $this->assertSame([], Contact::query()->parentsOf($parent)->pluck('id')->all());
    }

    #[Test]
    public function it_keeps_a_parent_whose_relationship_ended(): void
    {
        $vault = Vault::factory()->create();
        $parent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Relationship::factory()->parentage()->ended()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);

        $this->assertSame([$parent->id], Contact::query()->parentsOf($child)->pluck('id')->all());
    }

    #[Test]
    public function it_names_a_parent_once_however_many_rows_link_them_and_drops_a_deleted_row(): void
    {
        $vault = Vault::factory()->create();
        $parent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $stepParent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Relationship::factory()->parentage()->count(3)->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);

        Relationship::factory()->parentage()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $stepParent->id,
            'contact_b_id' => $child->id,
        ])->delete();

        $this->assertSame([$parent->id], Contact::query()->parentsOf($child)->pluck('id')->all());
    }

    #[Test]
    public function it_keeps_a_parent_written_with_a_type_since_renamed_or_withdrawn(): void
    {
        $vault = Vault::factory()->create();
        $parent = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        $type = RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create(['account_id' => $vault->account_id]);

        Relationship::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);

        $type->update(['name' => 'Progenitor', 'name_translation_key' => null, 'is_active' => false]);

        $this->assertSame([$parent->id], Contact::query()->parentsOf($child)->pluck('id')->all());
    }

    #[Test]
    public function it_reads_no_parent_from_a_type_merely_named_like_one(): void
    {
        $vault = Vault::factory()->create();
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $other = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        $invented = RelationshipType::factory()->create([
            'account_id' => $vault->account_id,
            'name' => 'Parent / Child',
            'role_a' => 'Parent',
            'role_b' => 'Child',
        ]);

        Relationship::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'relationship_type_id' => $invented->id,
            'contact_a_id' => $other->id,
            'contact_b_id' => $child->id,
        ]);

        $this->assertSame([], Contact::query()->parentsOf($child)->pluck('id')->all());
    }

    #[Test]
    public function it_reads_no_parent_from_another_vault_or_another_account(): void
    {
        $vault = Vault::factory()->create();
        $child = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $vault->account_id]);
        $parent = Contact::factory()->create(['vault_id' => $elsewhere->id, 'account_id' => $elsewhere->account_id]);

        Relationship::factory()->parentage()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);

        $this->assertSame([], Contact::query()->parentsOf($child)->pluck('id')->all());
    }

    #[Test]
    public function it_sends_the_search_engine_what_is_written_on_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $mose = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Mose', 'last_name' => 'Schrute']);
        $vegetarian = ValueListItem::factory()->create(['account_id' => $user->account_id, 'label' => 'Vegetarian']);

        $this->writeOn($dwight, FieldTypeEnum::Text, 'Employer', ['value_string' => 'Schrute Farms']);
        $this->writeOn($dwight, FieldTypeEnum::Select, 'Diet', ['value_list_item_id' => $vegetarian->id]);
        $this->writeOn($dwight, FieldTypeEnum::Relation, 'Cousin', ['related_contact_id' => $mose->id]);
        $this->writeOn($dwight, FieldTypeEnum::Boolean, 'Owns a farm', ['value_boolean' => true]);
        $this->writeOn($dwight, FieldTypeEnum::Text, 'Former employer', ['value_string' => 'Staples', 'is_detached' => true]);
        $this->writeOn($dwight, FieldTypeEnum::Text, 'Beet recipe', ['value_string' => 'Secret Schrute brine'], sensitive: true);

        $document = $dwight->refresh()->toSearchableArray();

        $this->assertSame($user->account_id, $document['account_id']);
        $this->assertSame($vault->id, $document['vault_id']);
        $this->assertNull($document['entity_type_id']);
        $this->assertSame('Dwight Schrute', $document['name']);
        $this->assertStringContainsString('Schrute Farms', (string) $document['body']);
        $this->assertStringContainsString('Vegetarian', (string) $document['body']);
        $this->assertStringContainsString('Mose Schrute', (string) $document['body']);
        $this->assertStringNotContainsString('Staples', (string) $document['body']);
        $this->assertStringNotContainsString('Yes', (string) $document['body']);
        $this->assertStringNotContainsString('brine', (string) $document['body']);
        $this->assertSame('Secret Schrute brine', $document['sensitive_body']);
    }

    #[Test]
    public function it_writes_what_is_sent_to_the_search_engine_in_the_language_of_its_account(): void
    {
        $user = User::factory()->create(['locale' => 'fr_FR']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->writeOn($dwight, FieldTypeEnum::Number, 'Beets sold', ['value_number' => '1234.5']);

        $this->assertStringContainsString('234,5', (string) $dwight->refresh()->toSearchableArray()['body']);
        $this->assertSame('en', app()->getLocale());
    }

    #[Test]
    public function it_names_where_the_words_searched_were_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);

        $this->writeOn($dwight, FieldTypeEnum::Text, 'Employer', ['value_string' => 'Schrute Farms']);
        $this->writeOn($dwight, FieldTypeEnum::Text, 'Beet recipe', ['value_string' => 'Secret brine'], sensitive: true);

        $dwight->refresh();

        $this->assertNull($dwight->matchedField(['Dwigth'], withSensitive: true));
        $this->assertSame(['label' => 'Employer', 'excerpt' => 'Schrute Farms'], $dwight->matchedField(['frams'], withSensitive: true));
        $this->assertSame(['label' => 'Beet recipe', 'excerpt' => null], $dwight->matchedField(['brine'], withSensitive: true));
        $this->assertNull($dwight->matchedField(['brine'], withSensitive: false));
    }

    #[Test]
    public function it_sends_itself_to_search_again_when_a_value_is_written_on_it(): void
    {
        $engine = $this->useRecordingSearchEngine();
        $dwight = Contact::factory()->create();
        $engine->forget();

        $this->writeOn($dwight, FieldTypeEnum::Text, 'Employer', ['value_string' => 'Schrute Farms']);

        $this->assertTrue($engine->wasSent($dwight));
    }

    #[Test]
    public function it_sends_the_records_naming_it_to_search_again_when_renamed(): void
    {
        $engine = $this->useRecordingSearchEngine();
        $vault = Vault::factory()->create();
        $jan = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);
        $michael = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Michael']);
        $dwight = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Dwight']);
        $pam = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam']);
        Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id, 'contact_a_id' => $michael->id, 'contact_b_id' => $jan->id]);
        $dinner = LifeEvent::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['account_id' => $vault->account_id, 'life_event_id' => $dinner->id, 'participant_type' => Contact::class, 'participant_id' => $jan->id]);
        $this->writeOn($dwight, FieldTypeEnum::Relation, 'Former boss', ['related_contact_id' => $jan->id]);
        $engine->forget();

        $jan->displayNameRepresentation->update(['family_name' => 'Gould']);
        $jan->refresh();

        $this->assertTrue($engine->wasSent($michael));
        $this->assertTrue($engine->wasSent($dinner));
        $this->assertTrue($engine->wasSent($dwight));
        $this->assertFalse($engine->wasSent($pam));
    }

    #[Test]
    public function it_sends_the_search_engine_the_names_of_the_people_it_is_related_to(): void
    {
        $vault = Vault::factory()->create();
        $michael = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
        $jan = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);
        $holly = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Holly', 'last_name' => 'Flax']);
        $partner = RelationshipType::factory()->create(['account_id' => $vault->account_id, 'role_a' => 'Partner', 'role_b' => 'Partner']);
        Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id, 'relationship_type_id' => $partner->id, 'contact_a_id' => $michael->id, 'contact_b_id' => $jan->id]);
        Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id, 'relationship_type_id' => $partner->id, 'contact_a_id' => $holly->id, 'contact_b_id' => $michael->id])->delete();

        $this->assertSame([['name' => 'Jan Levinson', 'label' => 'Partner of Jan Levinson']], $michael->searchableLinkedNames());
        $this->assertSame([['name' => 'Michael Scott', 'label' => 'Partner of Michael Scott']], $jan->searchableLinkedNames());
    }

    #[Test]
    public function it_is_found_in_the_list_by_every_way_its_name_is_written(): void
    {
        $vault = Vault::factory()->create();
        $minji = $this->minjiIn($vault);
        Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Kevin', 'last_name' => 'Malone']);

        foreach (['김민지', 'kim min-ji', '金敏智'] as $term) {
            $found = Contact::query()->where('vault_id', $vault->id)->matchingSearch($term)->get();

            $this->assertCount(1, $found, $term);
            $this->assertTrue($minji->is($found->first()));
        }

        $this->assertCount(0, Contact::query()->where('vault_id', $vault->id)->matchingSearch('Gim Minji')->get());
    }

    #[Test]
    public function it_sends_the_search_engine_every_way_its_name_is_written_the_shown_one_first(): void
    {
        $minji = $this->minjiIn(Vault::factory()->create());

        $this->assertSame("김민지\nKim Min-ji\n金敏智", $minji->searchableName());
    }

    #[Test]
    public function it_sends_the_search_engine_every_name_of_the_people_it_is_related_to(): void
    {
        $vault = Vault::factory()->create();
        $minji = $this->minjiIn($vault);
        $michael = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
        $colleague = RelationshipType::factory()->create(['account_id' => $vault->account_id, 'role_a' => 'Colleague', 'role_b' => 'Colleague']);
        Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id, 'relationship_type_id' => $colleague->id, 'contact_a_id' => $michael->id, 'contact_b_id' => $minji->id]);

        $this->assertSame([['name' => "김민지\nKim Min-ji\n金敏智", 'label' => 'Colleague of 김민지']], $michael->searchableLinkedNames());
    }

    private function minjiIn(Vault $vault): Contact
    {
        $minji = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => null, 'last_name' => null]);
        $hangul = ContactNameRepresentation::factory()->create(['contact_id' => $minji->id, 'language_tag' => 'ko', 'script_code' => 'Hang', 'formatted_name' => '김민지', 'given_name' => '민지', 'family_name' => '김', 'name_order' => NameOrderEnum::FamilyFirst, 'position' => 1]);
        ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $minji->id, 'language_tag' => 'ko', 'formatted_name' => 'Kim Min-ji', 'given_name' => 'Min-ji', 'family_name' => 'Kim', 'position' => 2]);
        ContactNameRepresentation::factory()->create(['contact_id' => $minji->id, 'language_tag' => 'ko', 'script_code' => 'Hani', 'formatted_name' => '金敏智', 'given_name' => '敏智', 'family_name' => '金', 'position' => 3]);
        $minji->display_name_representation_id = $hangul->id;
        $minji->save();

        return $minji->refresh();
    }

    /** @param  array<string, mixed>  $attributes */
    private function writeOn(Contact $contact, FieldTypeEnum $type, string $label, array $attributes, bool $sensitive = false): FieldValue
    {
        $field = Field::factory()
            ->state(['is_sensitive' => $sensitive])
            ->create(['account_id' => $contact->account_id, 'type' => $type, 'label' => $label]);

        return FieldValue::factory()->create(array_merge([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'value_string' => null,
        ], $attributes));
    }

    #[Test]
    public function it_copies_the_name_it_is_shown_under_into_its_own_columns(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        $married = ContactNameRepresentation::factory()->create([
            'contact_id' => $contact->id,
            'given_name' => 'Pam',
            'family_name' => 'Halpert',
        ]);

        $this->assertSame('Pam Beesly', $contact->refresh()->name);

        $contact->display_name_representation_id = $married->id;
        $contact->save();

        $this->assertSame('Halpert', $contact->last_name);
        $this->assertSame('Pam Halpert', $contact->name);
    }

    #[Test]
    public function it_keeps_its_address_and_its_place_in_the_list_when_another_name_changes(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $other = ContactNameRepresentation::factory()->create(['contact_id' => $contact->id]);
        $before = $contact->refresh()->only(['slug', 'sort_name', 'sort_key', 'search_key']);

        $other->update(['given_name' => 'Mose', 'family_name' => 'Schrute']);

        $this->assertSame($before, $contact->refresh()->only(['slug', 'sort_name', 'sort_key', 'search_key']));
    }

    #[Test]
    public function it_follows_its_displayed_name_when_that_one_changes(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Andy', 'last_name' => 'Bernard']);

        $contact->displayNameRepresentation->update(['given_name' => 'Andrew']);

        $this->assertSame('Andrew Bernard', $contact->refresh()->name);
        $this->assertSame('andrew-bernard', $contact->slug);
    }

    #[Test]
    public function it_reads_sorts_and_draws_a_name_written_only_as_a_whole(): void
    {
        $contact = Contact::factory()->create(['first_name' => null, 'last_name' => null]);
        $representation = ContactNameRepresentation::factory()->create([
            'contact_id' => $contact->id,
            'given_name' => null,
            'family_name' => null,
            'formatted_name' => '김민지',
        ]);

        $contact->display_name_representation_id = $representation->id;
        $contact->save();

        $this->assertSame('김민지', $contact->name);
        $this->assertSame('김민지', $contact->sort_name);
        $this->assertSame('김', $contact->initials);
        $this->assertFalse($contact->isNameless());
    }

    #[Test]
    public function it_finds_a_representation_by_script_or_by_kind(): void
    {
        $contact = Contact::factory()->create(['first_name' => null, 'last_name' => null]);
        $latin = ContactNameRepresentation::factory()->transliteration()->create([
            'contact_id' => $contact->id,
            'formatted_name' => 'Kim Min-ji',
        ]);
        $phonetic = ContactNameRepresentation::factory()->phonetic('x-rr')->create([
            'contact_id' => $contact->id,
            'formatted_name' => 'Gim Minji',
            'position' => 3,
        ]);

        $this->assertTrue($latin->is($contact->nameFor(script: 'Latn')));
        $this->assertTrue($phonetic->is($contact->nameFor(kind: ContactNameKindEnum::Phonetic)));
        $this->assertNull($contact->nameFor(script: 'Hani'));
    }
}
