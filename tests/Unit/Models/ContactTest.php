<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\NameOrderEnum;
use App\Exceptions\ContactNeedsAName;
use App\Models\Account;
use App\Models\Contact;
use App\Models\TemplateVersion;
use App\Models\Vault;
use Carbon\Carbon;
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
        $this->assertInstanceOf(Carbon::class, $contact->last_activity_at);
        $this->assertInstanceOf(Carbon::class, $contact->last_consulted_at);
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
    public function it_is_deleted_softly(): void
    {
        $contact = Contact::factory()->create(['last_name' => 'Schrute']);

        $contact->delete();

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
        $this->assertEquals(0, Contact::query()->count());
        $this->assertEquals(1, Contact::query()->withTrashed()->count());
    }
}
