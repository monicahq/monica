<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillContactSortNamesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_refiles_a_contact_saved_under_the_old_rule_and_rewrites_no_name(): void
    {
        $contact = $this->contactSavedUnderTheOldRule(locale: 'is_IS', givenName: 'Björk', familyName: 'Guðmundsdóttir');
        $names = $contact->only(Contact::NAME_PARTS);

        $this->migration()->up();

        $contact->refresh();
        $this->assertStringStartsWith('björk', $contact->sort_name);
        $this->assertSame($names, $contact->only(Contact::NAME_PARTS));
    }

    #[Test]
    public function it_leaves_an_english_contact_as_it_was(): void
    {
        $contact = $this->contactSavedUnderTheOldRule(locale: 'en', givenName: 'Stanley', familyName: 'Hudson');
        $before = $contact->only(['sort_name', 'sort_key', 'search_key', ...Contact::NAME_PARTS]);

        $this->migration()->up();

        $this->assertSame($before, $contact->refresh()->only(['sort_name', 'sort_key', 'search_key', ...Contact::NAME_PARTS]));
    }

    private function contactSavedUnderTheOldRule(string $locale, string $givenName, string $familyName): Contact
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id, 'locale' => $locale]);
        $contact = Contact::factory()->create(['account_id' => $account->id, 'first_name' => $givenName, 'middle_name' => null, 'last_name' => $familyName]);

        DB::table('contacts')->where('id', $contact->id)->update(['sort_name' => mb_strtolower("{$familyName} {$givenName}")]);

        return $contact->refresh();
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_21_000000_backfill_contact_sort_names.php');
    }
}
