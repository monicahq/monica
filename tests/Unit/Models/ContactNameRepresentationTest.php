<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\NameOrderEnum;
use App\Helpers\LocaleCollation;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactNameRepresentationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_its_sort_search_and_duplicate_keys_when_saved(): void
    {
        $representation = ContactNameRepresentation::factory()->create([
            'given_name' => 'Dwight',
            'additional_name' => 'Kurt',
            'family_name' => 'Schrute',
            'formatted_name' => null,
        ]);

        $this->assertSame('schrute dwight kurt', $representation->sort_name);
        $this->assertSame(LocaleCollation::foldForSearch('Dwight Kurt Schrute'), $representation->search_key);
        $this->assertSame(LocaleCollation::matchKey('Schrute'), $representation->match_key);
    }

    #[Test]
    public function it_refiles_its_contact_when_a_pronunciation_comes_and_goes_in_a_language_ordered_by_pronunciation(): void
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id, 'locale' => 'ja_JP']);
        $contact = Contact::factory()->create(['account_id' => $account->id, 'first_name' => '太郎', 'middle_name' => null, 'last_name' => '山田']);
        $before = $contact->refresh()->only(['sort_name', 'sort_key', 'search_key']);

        $pronunciation = ContactNameRepresentation::factory()->phonetic('x-other')->create(['contact_id' => $contact->id, 'script_code' => 'Hira', 'family_name' => 'やまだ', 'given_name' => 'たろう']);

        $contact->refresh();
        $this->assertSame('やまだ たろう', $contact->sort_name);
        $this->assertSame(LocaleCollation::sortKey('やまだ たろう', 'ja_JP'), $contact->sort_key);
        $this->assertSame($before['search_key'], $contact->search_key);

        $pronunciation->delete();

        $this->assertSame($before, $contact->refresh()->only(['sort_name', 'sort_key', 'search_key']));
    }

    #[Test]
    public function it_leaves_its_contact_unwritten_in_a_language_ordered_by_spelling(): void
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id, 'locale' => 'en']);
        $contact = Contact::factory()->create(['account_id' => $account->id, 'first_name' => 'Kevin', 'middle_name' => null, 'last_name' => 'Malone']);
        $updatedAt = $contact->refresh()->updated_at;

        $this->travel(1)->minutes();

        $pronunciation = ContactNameRepresentation::factory()->phonetic('ipa')->create(['contact_id' => $contact->id, 'script_code' => 'Hira', 'family_name' => 'まろーん', 'given_name' => 'けびん']);
        $pronunciation->delete();

        $this->assertEquals($updatedAt, $contact->refresh()->updated_at);
        $this->assertSame('malone kevin', $contact->sort_name);
    }

    #[Test]
    public function it_sorts_and_groups_a_name_written_only_as_a_whole(): void
    {
        $representation = ContactNameRepresentation::factory()->create([
            'given_name' => null,
            'family_name' => null,
            'formatted_name' => '김민지',
        ]);

        $this->assertSame('김민지', $representation->sort_name);
        $this->assertSame(LocaleCollation::matchKey('김민지'), $representation->match_key);
    }

    #[Test]
    public function it_shows_the_formatted_name_before_the_parts(): void
    {
        $representation = ContactNameRepresentation::factory()->make([
            'formatted_name' => 'Kim Min-ji',
            'given_name' => 'Min-ji',
            'family_name' => 'Kim',
        ]);

        $this->assertSame('Kim Min-ji', $representation->displayText());
    }

    #[Test]
    public function it_puts_the_parts_together_in_the_order_the_name_reads_in(): void
    {
        $givenFirst = ContactNameRepresentation::factory()->make([
            'prefix' => 'Dr',
            'given_name' => 'Michael',
            'additional_name' => 'Gary',
            'family_name' => 'Scott',
            'name_order' => null,
        ]);
        $familyFirst = ContactNameRepresentation::factory()->make([
            'given_name' => '민지',
            'family_name' => '김',
            'name_order' => NameOrderEnum::FamilyFirst,
        ]);

        $this->assertSame('Dr Michael Gary Scott', $givenFirst->displayText());
        $this->assertSame('김 민지', $familyFirst->displayText());
    }

    #[Test]
    public function it_knows_whether_it_is_the_name_its_contact_is_shown_under(): void
    {
        $contact = Contact::factory()->create();
        $secondary = ContactNameRepresentation::factory()->create(['contact_id' => $contact->id]);

        $this->assertTrue($contact->displayNameRepresentation->isDisplayed());
        $this->assertFalse($secondary->isDisplayed());
    }
}
