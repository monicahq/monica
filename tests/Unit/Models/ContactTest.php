<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Enums\NameOrderEnum;
use App\Exceptions\ContactNeedsAName;
use App\Helpers\LocaleCollation;
use App\Models\Account;
use App\Models\Contact;
use App\Models\File;
use App\Models\Reminder;
use App\Models\ShareLink;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($contact->account()->exists());
        $this->assertTrue($account->is($contact->account));
    }

    #[Test]
    public function it_belongs_to_a_vault(): void
    {
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->assertTrue($contact->vault()->exists());
        $this->assertTrue($vault->is($contact->vault));
    }

    #[Test]
    public function it_belongs_to_the_template_version_its_page_renders_from(): void
    {
        $version = TemplateVersion::factory()->published()->create();
        $contact = Contact::factory()->create(['template_version_id' => $version->id]);

        $this->assertTrue($contact->templateVersion()->exists());
        $this->assertTrue($version->is($contact->templateVersion));
    }

    #[Test]
    public function it_belongs_to_the_file_its_avatar_was_uploaded_as(): void
    {
        $vault = Vault::factory()->create();
        $file = File::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
        ]);
        $contact = Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'avatar_file_id' => $file->id,
        ]);

        $this->assertTrue($contact->avatar()->exists());
        $this->assertTrue($file->is($contact->avatar));
    }

    #[Test]
    public function it_has_no_avatar_when_it_is_shown_as_the_initials_the_product_draws(): void
    {
        $contact = Contact::factory()->create();

        $this->assertNull($contact->avatar_file_id);
        $this->assertNull($contact->avatar);
    }

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
        $this->assertEquals('uuid', $contact->getRouteKeyName());
        $this->assertEquals($contact->uuid, $contact->getRouteKey());
    }

    #[Test]
    public function it_casts_the_flags_the_counts_and_the_dates(): void
    {
        $contact = Contact::factory()->me()->favourite()->archived()->create([
            'name_order' => NameOrderEnum::FamilyFirst,
            'activity_count' => 12,
            'last_activity_at' => now(),
            'last_consulted_at' => now(),
        ]);

        $contact->refresh();

        $this->assertTrue($contact->is_me);
        $this->assertTrue($contact->is_favourite);
        $this->assertTrue($contact->is_archived);
        $this->assertFalse($contact->is_partial);
        $this->assertSame(12, $contact->activity_count);
        $this->assertEquals(NameOrderEnum::FamilyFirst, $contact->name_order);
        $this->assertInstanceOf(CarbonImmutable::class, $contact->last_activity_at);
        $this->assertInstanceOf(CarbonImmutable::class, $contact->last_consulted_at);
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

        $contact->last_name = 'Scarn';
        $contact->save();

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
    public function it_refuses_a_contact_with_no_part_of_a_name_at_all(): void
    {
        $this->expectException(ContactNeedsAName::class);

        Contact::factory()->create([
            'prefix' => 'Dr',
            'first_name' => null,
            'middle_name' => null,
            'last_name' => null,
            'nickname' => null,
            'maiden_name' => null,
        ]);
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
    public function it_groups_the_archived_apart_from_everybody_else(): void
    {
        $vault = Vault::factory()->create();
        $scott = Contact::factory()->archived()->create(['vault_id' => $vault->id, 'last_name' => 'Scott']);
        Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Beesly']);
        Contact::factory()->archived()->partial()->create(['vault_id' => $vault->id, 'first_name' => 'Jack']);

        $contacts = Contact::query()->inGrouping(ContactGroupingEnum::Archived)->get();

        $this->assertCount(1, $contacts);
        $this->assertTrue($scott->is($contacts->first()));
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
    public function it_sorts_by_a_column_in_the_direction_asked_for(): void
    {
        $vault = Vault::factory()->create();
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Michael', 'last_name' => 'Scott']);

        $descending = Contact::query()->sortedBy(ContactSortEnum::Name, 'desc')->pluck('last_name')->all();

        $this->assertEquals(['Scott', 'Beesly'], $descending);
    }

    #[Test]
    public function it_sorts_the_contacts_a_column_says_nothing_about_to_the_end_in_both_directions(): void
    {
        $vault = Vault::factory()->create();
        Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Beesly', 'last_activity_at' => null]);
        Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Schrute', 'last_activity_at' => CarbonImmutable::parse('2026-01-02')]);
        Contact::factory()->create(['vault_id' => $vault->id, 'last_name' => 'Scott', 'last_activity_at' => CarbonImmutable::parse('2026-01-01')]);

        $ascending = Contact::query()->sortedBy(ContactSortEnum::LastSpoke, 'asc')->pluck('last_name')->all();
        $descending = Contact::query()->sortedBy(ContactSortEnum::LastSpoke, 'desc')->pluck('last_name')->all();

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

        $order = Contact::query()->sortedBy(ContactSortEnum::Added, 'asc')->pluck('id')->all();

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

        $contact->last_name = 'Scarn';
        $contact->save();

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
}
