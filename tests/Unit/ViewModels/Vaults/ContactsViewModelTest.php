<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ContactGroupingEnum;
use App\Enums\ContactListColumnSourceEnum;
use App\Enums\ContactSortEnum;
use App\Enums\SortDirectionEnum;
use App\Models\Contact;
use App\Models\ContactListColumn;
use App\Models\ContactNameRepresentation;
use App\Models\File;
use App\Models\Section;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use App\ViewModels\Vaults\ContactsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactsViewModelTest extends TestCase
{
    use RefreshDatabase;

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
            route('files.variants.show', ['vault' => $vault, 'file' => $file, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]),
            $person['avatarUrl'],
        );
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
        $this->contact($vault, 'Holly', 'Flax', ['is_partial' => true, 'is_favourite' => true]);

        $chips = collect(new ContactsViewModel(user: $user, vault: $vault)->filters())
            ->mapWithKeys(fn (array $chip): array => [$chip['key'] => $chip['count']]);

        $this->assertEquals(2, $chips['all']);
        $this->assertEquals(1, $chips['favourites']);
        $this->assertEquals(2, $chips['names-only']);
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
        $this->assertNull($chips['names-only']);
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
            sort: ContactSortEnum::Added,
            direction: SortDirectionEnum::Descending,
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

        $this->assertEquals("/vaults/{$vault->slug}/contacts/all", $chips['all']);
        $this->assertEquals("/vaults/{$vault->slug}/contacts/favourites", $chips['favourites']);
        $this->assertArrayNotHasKey('archived', $chips);
        $this->assertEquals("/vaults/{$vault->slug}/contacts/names-only", $chips['names-only']);

        foreach (ContactGroupingEnum::cases() as $grouping) {
            $this->assertStringEndsWith("/contacts/{$grouping->value}", $chips[$grouping->value]);
        }

        $this->assertStringNotContainsString(
            'filter=',
            collect(new ContactsViewModel(user: $user, vault: $vault)->filters())->firstWhere('key', 'names-only')['url'],
        );
    }

    #[Test]
    public function it_aims_the_search_at_the_chip_in_force(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->assertStringEndsWith(
            '/contacts/all',
            new ContactsViewModel(user: $user, vault: $vault)->searchUrl(),
        );

        $this->assertStringEndsWith(
            '/contacts/names-only',
            new ContactsViewModel(user: $user, vault: $vault, grouping: ContactGroupingEnum::NamesOnly)->searchUrl(),
        );
    }

    #[Test]
    public function it_counts_what_is_showing_against_what_the_vault_holds(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');
        $this->contact($vault, 'Dwight', 'Schrute');

        $everybody = new ContactsViewModel(user: $user, vault: $vault);
        $this->assertSame('', $everybody->resultLine());
        $this->assertEquals('End of the list. 2 people, no pages.', $everybody->endLine());

        $narrowed = new ContactsViewModel(user: $user, vault: $vault, query: 'schrute');
        $this->assertEquals('1 of 2 shown.', $narrowed->resultLine());
        $this->assertEquals('End of the list. One person, no pages.', $narrowed->endLine());
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

    #[Test]
    public function it_reads_the_names_of_a_page_of_people_without_a_query_per_person(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $queriesFor = function (int $people) use ($user, $vault): int {
            Contact::query()->where('vault_id', $vault->id)->forceDelete();

            for ($index = 0; $index < $people; $index++) {
                $contact = $this->contact($vault, "Kevin{$index}", 'Malone');
                ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $contact->id, 'formatted_name' => "ケビン{$index}"]);
                ContactNameRepresentation::factory()->phonetic('ipa')->create(['contact_id' => $contact->id, 'formatted_name' => "ˈkɛvɪn{$index}"]);
            }

            DB::flushQueryLog();
            DB::enableQueryLog();
            new ContactsViewModel(user: $user, vault: $vault)->groups();
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $queriesFor(1);

        $this->assertSame($queriesFor(3), $queriesFor(12));
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
        $descending = $this->surnamesOrderedFor($user, $vault, SortDirectionEnum::Descending);

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
    private function surnamesOrderedFor(User $user, Vault $vault, SortDirectionEnum $direction = SortDirectionEnum::Ascending): array
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

    #[Test]
    public function it_shows_the_default_columns_until_the_vault_chooses_its_own(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $headings = new ContactsViewModel(user: $user, vault: $vault)->headings();

        $this->assertSame(['Name', 'Email', 'Phone number', 'Last spoke'], array_column($headings, 'label'));
    }

    #[Test]
    public function it_writes_a_row_the_way_it_is_read(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Dwight', 'Schrute');

        $person = new ContactsViewModel(user: $user, vault: $vault)->groups()[0]['people'][0];

        $this->assertEquals('Dwight Schrute', $person['name']);
        $this->assertFalse($person['isArchived']);
        $this->assertNull($person['avatarUrl']);
        $this->assertSame([[], [], []], array_column($person['cells'], 'values'));
        $this->assertSame(['—', '—', 'Not yet'], array_column($person['cells'], 'whenEmpty'));
    }

    #[Test]
    public function it_puts_the_favourites_first_under_a_heading_of_their_own(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Pam', 'Beesly');
        $this->contact($vault, 'Michael', 'Scott', ['is_favourite' => true]);
        $this->contact($vault, 'Dwight', 'Schrute');

        $groups = new ContactsViewModel(user: $user, vault: $vault)->groups();

        $this->assertSame(['Favourites', 'Everybody else'], array_column($groups, 'label'));
        $this->assertSame([1, 2], array_column($groups, 'count'));
        $this->assertEquals('Michael Scott', $groups[0]['people'][0]['name']);
        $this->assertFalse($groups[0]['people'][0]['showsStar']);
    }

    #[Test]
    public function it_does_not_split_a_list_holding_no_favourite_or_another_grouping(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Pam', 'Beesly');
        $this->contact($vault, 'Michael', 'Scott', ['is_favourite' => true]);

        $everybody = new ContactsViewModel(user: $user, vault: $vault, query: 'Pam')->groups();
        $favourites = new ContactsViewModel(user: $user, vault: $vault, grouping: ContactGroupingEnum::Favourites)->groups();

        $this->assertSame([null], array_column($everybody, 'label'));
        $this->assertSame([null], array_column($favourites, 'label'));
        $this->assertTrue($favourites[0]['people'][0]['showsStar']);
    }

    #[Test]
    public function it_repeats_no_heading_when_a_page_opens_inside_a_run(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', ['is_favourite' => true]);

        foreach (range(1, 51) as $number) {
            $this->contact($vault, "Schrute {$number}", 'Farms');
        }

        Paginator::currentPageResolver(fn (): int => 2);

        $groups = new ContactsViewModel(user: $user, vault: $vault)->groups();

        $this->assertCount(1, $groups);
        $this->assertNull($groups[0]['label']);
        $this->assertCount(2, $groups[0]['people']);
    }

    #[Test]
    public function it_leaves_out_a_hidden_column_and_one_no_template_supplies(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $section = Section::factory()->create(['account_id' => $user->account_id]);

        ContactListColumn::factory()->create(['vault_id' => $vault->id, 'source' => ContactListColumnSourceEnum::Email, 'heading' => 'Where to write', 'position' => 1]);
        ContactListColumn::factory()->create(['vault_id' => $vault->id, 'source' => ContactListColumnSourceEnum::Phone, 'is_visible' => false, 'position' => 2]);
        ContactListColumn::factory()->field($section, 'sales_region')->create(['vault_id' => $vault->id, 'position' => 3]);

        $headings = new ContactsViewModel(user: $user, vault: $vault)->headings();

        $this->assertSame(['Name', 'Where to write'], array_column($headings, 'label'));
    }

    #[Test]
    public function it_links_a_heading_to_its_order_and_says_which_way_the_list_runs(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $headings = new ContactsViewModel(user: $user, vault: $vault)->headings();

        $this->assertSame('ascending', $headings[0]['ariaSort']);
        $this->assertStringContainsString('sort=name&direction=desc', (string) $headings[0]['url']);
        $this->assertNull($headings[1]['url']);
        $this->assertNull($headings[3]['ariaSort']);
        $this->assertStringContainsString('sort=last-spoke&direction=desc', (string) $headings[3]['url']);
    }
}
