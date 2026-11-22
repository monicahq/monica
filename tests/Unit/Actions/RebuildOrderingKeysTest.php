<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RebuildOrderingKeys;
use App\Helpers\LocaleCollation;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\Entity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RebuildOrderingKeysTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_rebuilds_every_contact_key_for_the_new_language(): void
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id, 'last_name' => 'Işık']);

        new RebuildOrderingKeys(account: $account, locale: 'tr_TR')->execute();

        $fresh = $contact->fresh();

        $this->assertSame(LocaleCollation::sortKey($fresh->sort_name, 'tr_TR'), $fresh->sort_key);
        $this->assertSame($fresh->searchKeyFor('tr_TR'), $fresh->search_key);
    }

    #[Test]
    public function it_rebuilds_every_entity_key_for_the_new_language(): void
    {
        $account = Account::factory()->create();
        $entity = Entity::factory()->create(['account_id' => $account->id, 'name' => 'Ñuñez']);

        new RebuildOrderingKeys(account: $account, locale: 'es_ES')->execute();

        $fresh = $entity->fresh();

        $this->assertSame(LocaleCollation::sortKey($fresh->sort_name, 'es_ES'), $fresh->sort_key);
        $this->assertSame(LocaleCollation::foldForSearch($fresh->sort_name, 'es_ES'), $fresh->search_key);
    }

    #[Test]
    public function it_changes_a_key_that_the_new_language_writes_differently(): void
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create([
            'account_id' => $account->id,
            'first_name' => null,
            'middle_name' => null,
            'last_name' => 'Ñuñez',
        ]);

        new RebuildOrderingKeys(account: $account, locale: 'fr_FR')->execute();
        $french = $contact->fresh()->search_key;

        new RebuildOrderingKeys(account: $account, locale: 'es_ES')->execute();
        $spanish = $contact->fresh()->search_key;

        $this->assertSame('nunez', $french);
        $this->assertSame('ñuñez', $spanish);
    }

    #[Test]
    public function it_changes_nothing_when_it_runs_twice(): void
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id]);

        new RebuildOrderingKeys(account: $account, locale: 'fr_FR')->execute();
        $once = $contact->fresh()->only(['sort_key', 'search_key']);

        new RebuildOrderingKeys(account: $account, locale: 'fr_FR')->execute();

        $this->assertSame($once, $contact->fresh()->only(['sort_key', 'search_key']));
    }

    #[Test]
    public function it_leaves_another_account_alone(): void
    {
        $account = Account::factory()->create();
        $other = Account::factory()->create();
        Contact::factory()->create(['account_id' => $account->id]);
        $untouched = Contact::factory()->create(['account_id' => $other->id, 'last_name' => 'Işık']);

        $before = $untouched->fresh()->only(['sort_key', 'search_key']);

        new RebuildOrderingKeys(account: $account, locale: 'tr_TR')->execute();

        $this->assertSame($before, $untouched->fresh()->only(['sort_key', 'search_key']));
    }

    #[Test]
    public function it_rebuilds_a_deleted_contact_too_because_it_can_be_restored(): void
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id, 'last_name' => 'Irmak']);
        $contact->delete();

        new RebuildOrderingKeys(account: $account, locale: 'tr_TR')->execute();

        $trashed = Contact::withTrashed()->find($contact->id);

        $this->assertSoftDeleted($contact);
        $this->assertSame($trashed->searchKeyFor('tr_TR'), $trashed->search_key);
    }

    #[Test]
    public function it_refiles_every_contact_for_a_language_that_files_another_way(): void
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id, 'first_name' => 'Björk', 'middle_name' => null, 'last_name' => 'Guðmundsdóttir']);

        new RebuildOrderingKeys(account: $account, locale: 'is_IS')->execute();

        $rebuilt = $contact->fresh();

        $this->assertSame($rebuilt->sortNameFor('is_IS'), $rebuilt->sort_name);
        $this->assertStringStartsWith('björk', $rebuilt->sort_name);
        $this->assertSame(LocaleCollation::sortKey($rebuilt->sort_name, 'is_IS'), $rebuilt->sort_key);
        $this->assertSame($rebuilt->searchKeyFor('is_IS'), $rebuilt->search_key);
    }

    #[Test]
    public function it_refiles_a_contact_under_its_pronunciation_for_a_language_ordered_by_pronunciation(): void
    {
        $account = Account::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $account->id, 'first_name' => '太郎', 'middle_name' => null, 'last_name' => '山田']);
        ContactNameRepresentation::factory()->phonetic('x-other')->create(['contact_id' => $contact->id, 'script_code' => 'Hira', 'family_name' => 'やまだ', 'given_name' => 'たろう']);

        new RebuildOrderingKeys(account: $account, locale: 'ja_JP')->execute();

        $this->assertSame('やまだ たろう', $contact->fresh()->sort_name);
    }

    #[Test]
    public function it_leaves_the_sort_name_of_an_entity_as_it_is(): void
    {
        $account = Account::factory()->create();
        $entity = Entity::factory()->create(['account_id' => $account->id, 'name' => 'Dunder Mifflin']);
        $sortName = $entity->fresh()->sort_name;

        new RebuildOrderingKeys(account: $account, locale: 'is_IS')->execute();

        $this->assertSame($sortName, $entity->fresh()->sort_name);
    }
}
