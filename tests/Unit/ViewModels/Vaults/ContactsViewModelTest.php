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
            'created_at' => '2019-03-02 09:00:00',
        ]);

        $row = new ContactsViewModel(user: $user, vault: $vault)->rows()[0];

        $this->assertEquals('Michael Scott', $row['name']);
        $this->assertEquals('Prison Mike', $row['nickname']);
        $this->assertTrue($row['isFavourite']);
        $this->assertEquals('Aug 4, 2026', $row['lastSpoke']);
        $this->assertEquals('Mar 2, 2019', $row['added']);
        $this->assertEquals('Basic contact', $row['template']);
    }

    #[Test]
    public function it_says_so_rather_than_leaving_a_cell_blank(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Dwight', 'Schrute');

        $row = new ContactsViewModel(user: $user, vault: $vault)->rows()[0];

        $this->assertEquals('—', $row['nickname']);
        $this->assertEquals('—', $row['lastSpoke']);
    }

    #[Test]
    public function it_turns_the_column_being_sorted_on_around_and_starts_the_others_off(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $columns = collect(new ContactsViewModel(user: $user, vault: $vault)->columns());

        $name = $columns->firstWhere('key', 'name');
        $this->assertTrue($name['isSorted']);
        $this->assertTrue($name['isAscending']);
        $this->assertStringContainsString('direction=desc', $name['url']);

        $added = $columns->firstWhere('key', 'added');
        $this->assertFalse($added['isSorted']);
        $this->assertStringContainsString('sort=added', $added['url']);
        $this->assertStringContainsString('direction=desc', $added['url']);

        $nickname = $columns->firstWhere('key', 'nickname');
        $this->assertStringContainsString('direction=asc', $nickname['url']);
    }

    #[Test]
    public function it_locks_the_name_and_refuses_to_sort_on_the_template(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $columns = collect(new ContactsViewModel(user: $user, vault: $vault)->columns());

        $this->assertTrue($columns->firstWhere('key', 'name')['isLocked']);
        $this->assertTrue($columns->firstWhere('key', 'name')['isSortable']);
        $this->assertFalse($columns->firstWhere('key', 'template')['isLocked']);
        $this->assertFalse($columns->firstWhere('key', 'template')['isSortable']);
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
    public function it_says_what_the_list_is_ordered_by(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->assertEquals(
            'Sorted by name, ascending',
            new ContactsViewModel(user: $user, vault: $vault)->sortLine(),
        );

        $this->assertEquals(
            'Sorted by last spoke, descending',
            new ContactsViewModel(
                user: $user,
                vault: $vault,
                sort: ContactsViewModel::SORT_LAST_SPOKE,
                direction: ContactsViewModel::DIRECTION_DESC,
            )->sortLine(),
        );
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
