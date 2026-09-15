<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Enums\StructureAppliesToEnum;
use App\Helpers\LocaleCollation;
use App\Models\Contact;
use App\Models\File;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use App\ViewModels\Vaults\ContactsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_row_the_way_it_is_read(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', [
            'nickname' => 'Prison Mike',
            'is_favourite' => true,
            'last_activity_at' => '2026-08-04 09:00:00',
        ]);

        $person = new ContactsViewModel(user: $user, vault: $vault)->groups()[0]['people'][0];

        $this->assertEquals('Michael Scott', $person['name']);
        $this->assertEquals('Prison Mike', $person['nickname']);
        $this->assertTrue($person['isFavourite']);
        $this->assertFalse($person['isArchived']);
        $this->assertEquals('Aug 4, 2026', $person['when']);
        $this->assertEquals('last spoke', $person['whenLabel']);
        $this->assertEquals('Basic contact', $person['template']);
        $this->assertNull($person['avatarUrl']);
    }

    #[Test]
    public function it_says_where_the_picture_of_a_row_is_read_from(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $file = File::factory()->image()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);

        $this->contact($vault, 'Pam', 'Beesly', ['avatar_file_id' => $file->id]);

        $person = new ContactsViewModel(user: $user, vault: $vault)->groups()[0]['people'][0];

        $this->assertEquals(
            route('files.show', ['vault' => $vault, 'file' => $file]),
            $person['avatarUrl'],
        );
    }

    #[Test]
    public function it_ends_the_row_on_the_day_somebody_was_written_down_until_they_have_been_spoken_to(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Dwight', 'Schrute', ['created_at' => '2019-03-02 09:00:00']);

        $person = new ContactsViewModel(user: $user, vault: $vault)->groups()[0]['people'][0];

        $this->assertNull($person['nickname']);
        $this->assertEquals('Mar 2, 2019', $person['when']);
        $this->assertEquals('added', $person['whenLabel']);
    }

    #[Test]
    public function it_groups_the_rows_under_the_letter_they_begin_with(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');
        $this->contact($vault, 'Dwight', 'Schrute');
        $this->contact($vault, 'Pam', 'Beesly');

        $groups = new ContactsViewModel(user: $user, vault: $vault)->groups();

        $this->assertCount(2, $groups);
        $this->assertEquals('B', $groups[0]['letter']);
        $this->assertEquals('Pam Beesly', $groups[0]['people'][0]['name']);
        $this->assertEquals('S', $groups[1]['letter']);
        $this->assertCount(2, $groups[1]['people']);
    }

    #[Test]
    public function it_leaves_the_letters_off_a_list_that_is_not_in_name_order(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');
        $this->contact($vault, 'Pam', 'Beesly');

        $groups = new ContactsViewModel(
            user: $user,
            vault: $vault,
            sort: ContactSortEnum::Added->value,
        )->groups();

        $this->assertCount(1, $groups);
        $this->assertNull($groups[0]['letter']);
        $this->assertCount(2, $groups[0]['people']);
    }

    #[Test]
    public function it_leaves_the_letter_off_a_run_the_page_before_it_had_already_started(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        foreach (range(1, 51) as $number) {
            $this->contact($vault, "Schrute {$number}", 'Farms');
        }

        Paginator::currentPageResolver(fn (): int => 2);

        $groups = new ContactsViewModel(user: $user, vault: $vault)->groups();

        $this->assertCount(1, $groups);
        $this->assertNull($groups[0]['letter']);
        $this->assertCount(1, $groups[0]['people']);
    }

    #[Test]
    public function it_counts_the_people_each_chip_would_show(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', ['is_favourite' => true]);
        $this->contact($vault, 'Dwight', 'Schrute');
        $this->contact($vault, 'Devon', 'White', ['is_archived' => true]);
        $this->contact($vault, 'Bob', 'Vance', ['is_partial' => true]);

        $chips = collect(new ContactsViewModel(user: $user, vault: $vault)->filters())
            ->mapWithKeys(fn (array $chip): array => [$chip['key'] => $chip['count']]);

        $this->assertEquals(2, $chips['all']);
        $this->assertEquals(1, $chips['favourites']);
        $this->assertEquals(1, $chips['archived']);
    }

    #[Test]
    public function it_leaves_the_number_off_a_chip_with_nobody_behind_it(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');

        $chips = collect(new ContactsViewModel(user: $user, vault: $vault)->filters())
            ->mapWithKeys(fn (array $chip): array => [$chip['key'] => $chip['count']]);

        $this->assertEquals(1, $chips['all']);
        $this->assertNull($chips['favourites']);
        $this->assertNull($chips['archived']);
    }

    #[Test]
    public function it_counts_the_vault_in_the_bar_whatever_the_list_is_showing(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');
        $this->contact($vault, 'Dwight', 'Schrute');

        $this->assertEquals(
            2,
            new ContactsViewModel(user: $user, vault: $vault, query: 'schrute')->headcount(),
        );
    }

    #[Test]
    public function it_keeps_the_search_and_the_sort_while_a_chip_changes_the_filter(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $viewModel = new ContactsViewModel(
            user: $user,
            vault: $vault,
            query: 'schrute',
            sort: ContactSortEnum::Added->value,
            direction: ContactsViewModel::DIRECTION_DESC,
        );

        $favourites = collect($viewModel->filters())->firstWhere('key', 'favourites');

        $this->assertStringContainsString('query=schrute', $favourites['url']);
        $this->assertStringContainsString('sort=added', $favourites['url']);
        $this->assertStringContainsString('direction=desc', $favourites['url']);
        $this->assertFalse($favourites['isCurrent']);
    }

    #[Test]
    public function it_puts_the_chips_in_the_path_rather_than_the_query_string(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $chips = collect(new ContactsViewModel(user: $user, vault: $vault)->filters())
            ->mapWithKeys(fn (array $chip): array => [$chip['key'] => parse_url($chip['url'], PHP_URL_PATH)]);

        $this->assertEquals("/vaults/{$vault->slug}/contacts", $chips['all']);
        $this->assertEquals("/vaults/{$vault->slug}/contacts/favourites", $chips['favourites']);
        $this->assertEquals("/vaults/{$vault->slug}/contacts/archived", $chips['archived']);

        $this->assertStringNotContainsString(
            'filter=',
            collect(new ContactsViewModel(user: $user, vault: $vault)->filters())->firstWhere('key', 'archived')['url'],
        );
    }

    #[Test]
    public function it_aims_the_search_at_the_chip_in_force(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->assertStringEndsWith(
            '/contacts',
            new ContactsViewModel(user: $user, vault: $vault)->searchUrl(),
        );

        $this->assertStringEndsWith(
            '/contacts/archived',
            new ContactsViewModel(user: $user, vault: $vault, filter: ContactGroupingEnum::Archived->value)->searchUrl(),
        );
    }

    #[Test]
    public function it_falls_back_to_the_name_when_the_url_asks_for_nonsense(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $viewModel = new ContactsViewModel(
            user: $user,
            vault: $vault,
            filter: 'beets',
            sort: 'shoe-size',
            direction: 'sideways',
        );

        $this->assertEquals(ContactGroupingEnum::Everyone->value, $viewModel->filter());
        $this->assertEquals(ContactSortEnum::Name->value, $viewModel->sort());
        $this->assertEquals(ContactsViewModel::DIRECTION_ASC, $viewModel->direction());
    }

    #[Test]
    public function it_counts_what_is_showing_against_what_the_vault_holds(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');
        $this->contact($vault, 'Dwight', 'Schrute');

        $everybody = new ContactsViewModel(user: $user, vault: $vault);
        $this->assertEquals('All 2 people in this vault, on a single list.', $everybody->resultLine());
        $this->assertEquals('End of the list. 2 people, no pages.', $everybody->endLine());

        $narrowed = new ContactsViewModel(user: $user, vault: $vault, query: 'schrute');
        $this->assertEquals('1 of 2 shown.', $narrowed->resultLine());
        $this->assertEquals('End of the list. One person, no pages.', $narrowed->endLine());
    }

    #[Test]
    public function it_says_nothing_about_who_is_in_a_vault_that_holds_nobody(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $empty = new ContactsViewModel(user: $user, vault: $vault);

        $this->assertSame('', $empty->resultLine());
    }

    #[Test]
    public function it_says_nothing_about_the_end_of_a_list_that_never_started(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');

        $matchingNobody = new ContactsViewModel(user: $user, vault: $vault, query: 'beesly');

        $this->assertSame('', $matchingNobody->endLine());
    }

    #[Test]
    public function it_offers_the_order_in_the_words_of_what_is_being_ordered(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $byName = collect(new ContactsViewModel(user: $user, vault: $vault)->directions());
        $this->assertEquals(['A to Z', 'Z to A'], $byName->pluck('label')->all());
        $this->assertTrue($byName->firstWhere('value', 'asc')['isCurrent']);

        $byDate = collect(new ContactsViewModel(
            user: $user,
            vault: $vault,
            sort: ContactSortEnum::LastSpoke->value,
            direction: ContactsViewModel::DIRECTION_DESC,
        )->directions());

        $this->assertEquals(['Newest first', 'Oldest first'], $byDate->pluck('label')->all());
        $this->assertTrue($byDate->firstWhere('value', 'desc')['isCurrent']);
    }

    #[Test]
    public function it_marks_the_order_the_list_is_in(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $sorts = collect(new ContactsViewModel(
            user: $user,
            vault: $vault,
            sort: ContactSortEnum::Added->value,
        )->sorts());

        $this->assertEquals(['name', 'nickname', 'last-spoke', 'added'], $sorts->pluck('value')->all());
        $this->assertTrue($sorts->firstWhere('value', 'added')['isCurrent']);
        $this->assertFalse($sorts->firstWhere('value', 'name')['isCurrent']);
    }

    #[Test]
    public function it_tells_an_empty_vault_apart_from_a_search_that_matched_nobody(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $empty = new ContactsViewModel(user: $user, vault: $vault);
        $this->assertTrue($empty->vaultIsEmpty());
        $this->assertFalse($empty->noResults());

        $this->contact($vault, 'Michael', 'Scott');

        $missed = new ContactsViewModel(user: $user, vault: $vault, query: 'Filippelli');
        $this->assertFalse($missed->vaultIsEmpty());
        $this->assertTrue($missed->noResults());
    }

    /** @param  array<string, mixed>  $attributes */
    private function contact(Vault $vault, string $firstName, string $lastName, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'structure_version_id' => $vault->defaultTemplates()->sole()->structure_version_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            ...$attributes,
        ]);
    }

    private function vaultReadyForContacts(User $user): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Basic contact',
        ]);

        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $template->structure->update(['published_version_id' => $version->id]);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        return $vault;
    }

    #[Test]
    public function it_orders_the_list_by_the_alphabet_of_the_language_the_reader_reads_in(): void
    {
        $user = $this->readerIn('fr_FR');
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Dwight', 'Schrute');
        $this->contact($vault, 'Elodie', 'Élodie');
        $this->contact($vault, 'Erin', 'Erin');

        $this->assertSame(
            ['Élodie', 'Erin', 'Schrute'],
            $this->surnamesOrderedFor($user, $vault),
        );
    }

    #[Test]
    public function it_files_a_letter_carrying_a_ring_with_its_plain_letter(): void
    {
        $user = $this->readerIn('de_DE');
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');
        $this->contact($vault, 'Anders', 'Ångström');
        $this->contact($vault, 'Angela', 'Martin');

        $this->assertSame(
            ['Ångström', 'Martin', 'Scott'],
            $this->surnamesOrderedFor($user, $vault),
        );
    }

    #[Test]
    public function it_reads_the_other_way_round_as_the_exact_reverse(): void
    {
        $user = $this->readerIn('fr_FR');
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Dwight', 'Schrute');
        $this->contact($vault, 'Elodie', 'Élodie');
        $this->contact($vault, 'Erin', 'Erin');

        $ascending = $this->surnamesOrderedFor($user, $vault);
        $descending = $this->surnamesOrderedFor($user, $vault, ContactsViewModel::DIRECTION_DESC);

        $this->assertSame(array_reverse($ascending), $descending);
    }

    #[Test]
    public function it_shows_everybody_exactly_once_across_the_pages(): void
    {
        $user = $this->readerIn('fr_FR');
        $vault = $this->vaultReadyForContacts($user);

        for ($index = 1; $index <= 55; $index++) {
            $this->contact($vault, 'Erin', $index % 5 === 0 ? "Élodie {$index}" : "Bernard {$index}");
        }

        $seen = [];
        $page = 1;

        do {
            $this->app['request']->merge(['page' => $page]);
            $viewModel = new ContactsViewModel(user: $user, vault: $vault);

            foreach ($viewModel->groups() as $group) {
                foreach ($group['people'] as $person) {
                    $seen[] = $person['name'];
                }
            }

            $page++;
        } while ($page <= 3);

        $this->assertCount(55, $seen);
        $this->assertCount(55, array_unique($seen));
    }

    private function readerIn(string $locale): User
    {
        $user = User::factory()->create(['locale' => $locale]);
        $this->app->setLocale($locale);

        return $user;
    }

    /** @return array<int, string> */
    private function surnamesOrderedFor(User $user, Vault $vault, string $direction = ContactsViewModel::DIRECTION_ASC): array
    {
        $viewModel = new ContactsViewModel(user: $user, vault: $vault, direction: $direction);

        $surnames = [];

        foreach ($viewModel->groups() as $group) {
            foreach ($group['people'] as $person) {
                $surnames[] = mb_substr($person['name'], mb_strpos($person['name'], ' ') + 1);
            }
        }

        return $surnames;
    }

    #[Test]
    public function it_files_an_accented_name_under_the_letter_its_language_files_it_under(): void
    {
        $user = $this->readerIn('fr_FR');
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Elodie', 'Élodie');
        $this->contact($vault, 'Erin', 'Erin');

        $groups = new ContactsViewModel(user: $user, vault: $vault)->groups();

        $this->assertCount(1, $groups);
        $this->assertSame('E', $groups[0]['letter']);
        $this->assertCount(2, $groups[0]['people']);
    }

    #[Test]
    public function it_keeps_the_turkish_dotted_and_dotless_i_under_their_own_headings(): void
    {
        $user = $this->readerIn('tr_TR');
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Deniz', 'ışık');
        $this->contact($vault, 'Deniz', 'irmak');

        $letters = array_column(new ContactsViewModel(user: $user, vault: $vault)->groups(), 'letter');

        $this->assertContains('I', $letters);
        $this->assertContains('İ', $letters);
        $this->assertCount(2, $letters);
    }

    #[Test]
    public function it_files_a_name_that_does_not_begin_with_a_letter_under_one_catch_all_heading(): void
    {
        $user = $this->readerIn('en');
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Ryan', '3 amigos');
        $this->contact($vault, 'Kelly', '"quoted"');

        $letters = array_column(new ContactsViewModel(user: $user, vault: $vault)->groups(), 'letter');

        $this->assertSame(['#'], array_values(array_unique($letters)));
    }

    #[Test]
    public function it_gives_no_heading_that_disagrees_with_the_people_under_it(): void
    {
        $user = $this->readerIn('fr_FR');
        $vault = $this->vaultReadyForContacts($user);

        foreach (['Élodie', 'Erin', 'Ångström', 'Schrute', 'Œuvre', 'ñuñez'] as $surname) {
            $this->contact($vault, 'Reader', $surname);
        }

        $disagreements = [];

        foreach (new ContactsViewModel(user: $user, vault: $vault)->groups() as $group) {
            foreach ($group['people'] as $person) {
                $surname = mb_substr($person['name'], mb_strpos($person['name'], ' ') + 1);

                if (LocaleCollation::headingLetter(mb_strtolower($surname), 'fr_FR') !== $group['letter']) {
                    $disagreements[] = $surname;
                }
            }
        }

        $this->assertSame([], $disagreements);
    }

    #[Test]
    public function it_finds_a_french_name_typed_without_its_accent(): void
    {
        $user = $this->readerIn('fr_FR');
        $vault = $this->vaultReadyForContacts($user);
        $this->contact($vault, 'Dwight', 'Élodie');

        $this->assertSame(['Élodie'], $this->surnamesMatching($user, $vault, 'elodie'));
    }

    #[Test]
    public function it_finds_a_german_name_typed_without_its_umlaut(): void
    {
        $user = $this->readerIn('de_DE');
        $vault = $this->vaultReadyForContacts($user);
        $this->contact($vault, 'Anna', 'Müller');

        $this->assertSame(['Müller'], $this->surnamesMatching($user, $vault, 'muller'));
    }

    #[Test]
    public function it_still_finds_a_name_typed_with_its_accent(): void
    {
        $user = $this->readerIn('fr_FR');
        $vault = $this->vaultReadyForContacts($user);
        $this->contact($vault, 'Dwight', 'Élodie');

        $this->assertSame(['Élodie'], $this->surnamesMatching($user, $vault, 'Élodie'));
    }

    #[Test]
    public function it_does_not_find_a_spanish_enye_typed_as_an_n_because_spanish_calls_it_another_letter(): void
    {
        $user = $this->readerIn('es_ES');
        $vault = $this->vaultReadyForContacts($user);
        $this->contact($vault, 'Carlos', 'Ñuñez');

        $this->assertSame([], $this->surnamesMatching($user, $vault, 'nunez'));
        $this->assertSame(['Ñuñez'], $this->surnamesMatching($user, $vault, 'ñuñez'));
    }

    #[Test]
    public function it_does_not_find_a_turkish_dotless_i_typed_as_a_dotted_one_because_turkish_calls_them_their_own(): void
    {
        $user = $this->readerIn('tr_TR');
        $vault = $this->vaultReadyForContacts($user);
        $this->contact($vault, 'Deniz', 'ışık');

        $this->assertSame([], $this->surnamesMatching($user, $vault, 'isik'));
        $this->assertSame(['ışık'], $this->surnamesMatching($user, $vault, 'ışık'));
    }

    #[Test]
    public function it_keeps_matching_anywhere_in_the_name_and_not_only_at_its_start(): void
    {
        $user = $this->readerIn('fr_FR');
        $vault = $this->vaultReadyForContacts($user);
        $this->contact($vault, 'Dwight', 'Bérnard');

        $this->assertSame(['Bérnard'], $this->surnamesMatching($user, $vault, 'rnard'));
    }

    #[Test]
    public function it_keeps_finding_somebody_by_their_nickname(): void
    {
        $user = $this->readerIn('fr_FR');
        $vault = $this->vaultReadyForContacts($user);
        $this->contact($vault, 'Michael', 'Scott', ['nickname' => 'Prison Mike']);

        $this->assertSame(['Scott'], $this->surnamesMatching($user, $vault, 'prison'));
    }

    /** @return array<int, string> */
    private function surnamesMatching(User $user, Vault $vault, string $term): array
    {
        $viewModel = new ContactsViewModel(user: $user, vault: $vault, query: $term);

        $surnames = [];

        foreach ($viewModel->groups() as $group) {
            foreach ($group['people'] as $person) {
                $surnames[] = mb_substr($person['name'], mb_strpos($person['name'], ' ') + 1);
            }
        }

        return $surnames;
    }

    #[Test]
    public function it_orders_the_list_exactly_as_the_collator_would(): void
    {
        $user = $this->readerIn('fr_FR');
        $vault = $this->vaultReadyForContacts($user);

        $surnames = ['Schrute', 'Élodie', 'Erin', 'Ångström', 'Œuvre', 'ñuñez', 'Zoe', 'Anzù'];

        foreach ($surnames as $surname) {
            $this->contact($vault, 'Reader', $surname);
        }

        $expected = new Collection($surnames)
            ->sortByCollator(fn (string $surname): string => mb_strtolower($surname))
            ->all();

        $this->assertSame($expected, $this->surnamesOrderedFor($user, $vault));
    }
}
