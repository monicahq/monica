<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\TemplateAppliesToEnum;
use App\Models\Contact;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use App\ViewModels\Vaults\ContactsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\Paginator;
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
            sort: ContactsViewModel::SORT_ADDED,
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
            sort: ContactsViewModel::SORT_ADDED,
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

        $this->assertEquals("/vaults/{$vault->uuid}/contacts", $chips['all']);
        $this->assertEquals("/vaults/{$vault->uuid}/contacts/favourites", $chips['favourites']);
        $this->assertEquals("/vaults/{$vault->uuid}/contacts/archived", $chips['archived']);

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
            new ContactsViewModel(user: $user, vault: $vault, filter: ContactsViewModel::FILTER_ARCHIVED)->searchUrl(),
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

        $this->assertEquals(ContactsViewModel::FILTER_ALL, $viewModel->filter());
        $this->assertEquals(ContactsViewModel::SORT_NAME, $viewModel->sort());
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
        $this->assertEquals('All 2 people in this vault, on one scrolling list.', $everybody->resultLine());
        $this->assertEquals('End of the list. 2 people, no pages.', $everybody->endLine());

        $narrowed = new ContactsViewModel(user: $user, vault: $vault, query: 'schrute');
        $this->assertEquals('1 of 2 shown.', $narrowed->resultLine());
        $this->assertEquals('End of the list. One person, no pages.', $narrowed->endLine());
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
            sort: ContactsViewModel::SORT_LAST_SPOKE,
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
            sort: ContactsViewModel::SORT_ADDED,
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function contact(Vault $vault, string $firstName, string $lastName, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'template_version_id' => $vault->defaultTemplates()->sole()->template_version_id,
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

        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $template->published_version_id = $version->id;
        $template->save();

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => TemplateAppliesToEnum::Contact,
            'template_version_id' => $version->id,
        ]);

        return $vault;
    }
}
