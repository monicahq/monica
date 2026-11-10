<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\FieldTypeEnum;
use App\Enums\NameOrderEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\PageSize;
use App\Jobs\RecordContactVisit;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_everybody_in_the_vault(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', ['nickname' => 'Prison Mike']);
        $this->contact($vault, 'Dwight', 'Schrute');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertStatus(200)
            ->assertSee('Michael Scott')
            ->assertSee('Dwight Schrute')
            ->assertSeeInOrder(['Name', 'Email', 'Phone number', 'Last spoke'])
            ->assertSee(route('contactListColumns.edit', $vault))
            ->assertSee('aria-label="Customise view"', escape: false)
            ->assertSee('Manage duplicates')
            ->assertSee(route('vaults.duplicates.index', $vault));
    }

    #[Test]
    public function it_lists_nobody_from_another_vault(): void
    {
        $user = User::factory()->create();
        $scranton = $this->vaultReadyForContacts($user);
        $utica = $this->vaultReadyForContacts($user);

        $this->contact($scranton, 'Michael', 'Scott');
        $this->contact($utica, 'Karen', 'Filippelli');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $scranton, 'filter' => 'all']))
            ->assertSee('Michael Scott')
            ->assertDontSee('Karen Filippelli');
    }

    #[Test]
    public function it_leaves_out_the_people_the_main_list_never_shows(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');
        $this->contact($vault, 'Dwight', 'Schrute', ['is_archived' => true]);
        $this->contact($vault, 'Bob', 'Vance', ['is_partial' => true]);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertSee('Michael Scott')
            ->assertDontSee('Dwight Schrute')
            ->assertDontSee('Bob Vance');
    }

    #[Test]
    public function it_offers_no_archived_list(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get("/vaults/{$vault->slug}/contacts/archived")->assertNotFound();
    }

    #[Test]
    public function it_shows_only_the_favourites_under_their_own_chip(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', ['is_favourite' => true]);
        $this->contact($vault, 'Dwight', 'Schrute');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'favourites']))
            ->assertSee('Michael Scott')
            ->assertDontSee('Dwight Schrute');
    }

    #[Test]
    public function it_lists_everybody_at_its_own_address(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');
        $this->contact($vault, 'Dwight', 'Schrute', ['is_archived' => true]);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertStatus(200)
            ->assertSee('Michael Scott')
            ->assertDontSee('Dwight Schrute');
    }

    #[Test]
    public function it_shows_the_people_written_down_as_a_first_name_only(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');
        $this->contact($vault, 'Bob', 'Vance', ['is_partial' => true]);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'names-only']))
            ->assertStatus(200)
            ->assertSee('Bob Vance')
            ->assertDontSee('Michael Scott');
    }

    #[Test]
    public function it_does_not_find_a_contact_list_at_a_word_that_is_not_a_grouping(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get("/vaults/{$vault->slug}/contacts/beets")
            ->assertStatus(404);
    }

    #[Test]
    public function it_moves_the_address_bar_with_the_view(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $page = $this->actingAs($user)
            ->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertStatus(200)
            ->getContent();

        preg_match_all('/x-target(\.push)?="contacts"/', $page, $targets);

        $this->assertCount(4, $targets[0]);
        $this->assertSame($targets[0], array_filter($targets[0], fn (string $target): bool => str_contains($target, '.push')));
    }

    #[Test]
    public function it_shows_everybody_when_nothing_is_recorded(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertRedirect(route('contacts.index', [
                'vault' => $vault,
                'filter' => 'all',
                'sort' => 'name',
                'direction' => 'asc',
            ]));
    }

    #[Test]
    public function it_keeps_the_categories_when_it_redirects_the_bare_address(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'categories' => [7]]))
            ->assertRedirect(route('contacts.index', [
                'vault' => $vault,
                'filter' => 'all',
                'sort' => 'name',
                'direction' => 'asc',
                'categories' => [7],
            ]));
    }

    #[Test]
    public function it_offers_the_categories_held_in_the_vault_to_narrow_the_list(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $user->account_id]);
        $sales = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Sales']);
        $field = Field::factory()->create(['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);
        $jim = $this->contact($vault, 'Jim', 'Halpert');
        $this->contact($vault, 'Toby', 'Flenderson');
        FieldValue::factory()->create(['valuable_id' => $jim->id, 'field_id' => $field->id, 'value_string' => null, 'value_list_item_ids' => [$sales->id]]);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'categories' => [$sales->id]]))
            ->assertOk()
            ->assertSee('Categories')
            ->assertSee('name="categories[]"', false)
            ->assertSee('Halpert')
            ->assertDontSee('Flenderson');
    }

    #[Test]
    public function it_refuses_a_category_outside_the_accounts_list_of_categories(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $user->account_id]);
        $elsewhere = ValueListItem::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'categories' => [$elsewhere->id]]))
            ->assertSessionHasErrors('categories.0');
    }

    #[Test]
    public function it_redirects_the_bare_address_to_the_recorded_view(): void
    {
        $user = User::factory()->create([
            'contact_list_grouping' => 'favourites',
            'contact_list_sort' => 'last-spoke',
            'contact_list_direction' => 'desc',
        ]);
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertRedirect(route('contacts.index', [
                'vault' => $vault,
                'filter' => 'favourites',
                'sort' => 'last-spoke',
                'direction' => 'desc',
            ]));
    }

    #[Test]
    public function it_records_the_view_the_address_states(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.index', [
            'vault' => $vault,
            'filter' => 'names-only',
            'sort' => 'added',
            'direction' => 'desc',
        ]))->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'contact_list_grouping' => 'names-only',
            'contact_list_sort' => 'added',
            'contact_list_direction' => 'desc',
        ]);
    }

    #[Test]
    public function it_applies_the_recorded_view_in_every_vault_of_the_account(): void
    {
        $user = User::factory()->create();
        $scranton = $this->vaultReadyForContacts($user);
        $stamford = $this->vaultReadyForContacts($user, 'Another contact');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $scranton, 'filter' => 'names-only']))
            ->assertStatus(200);

        $this->actingAs($user)->get(route('contacts.index', $stamford))
            ->assertRedirect(route('contacts.index', [
                'vault' => $stamford,
                'filter' => 'names-only',
                'sort' => 'name',
                'direction' => 'asc',
            ]));
    }

    #[Test]
    public function it_shows_the_grouping_the_address_states_over_the_recorded_one(): void
    {
        $user = User::factory()->create(['contact_list_grouping' => 'favourites']);
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', ['is_favourite' => true]);
        $this->contact($vault, 'Dwight', 'Schrute', ['is_partial' => true]);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'names-only']))
            ->assertStatus(200)
            ->assertSee('Dwight Schrute')
            ->assertDontSee('Michael Scott');

        $this->assertEquals('names-only', $user->refresh()->contact_list_grouping);
    }

    #[Test]
    public function it_keeps_the_recorded_sort_when_the_address_states_only_a_grouping(): void
    {
        $user = User::factory()->create([
            'contact_list_grouping' => 'all',
            'contact_list_sort' => 'last-spoke',
            'contact_list_direction' => 'desc',
        ]);
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'names-only']))
            ->assertStatus(200);

        $user->refresh();

        $this->assertEquals('names-only', $user->contact_list_grouping);
        $this->assertEquals('last-spoke', $user->contact_list_sort);
        $this->assertEquals('desc', $user->contact_list_direction);
    }

    #[Test]
    public function it_falls_back_to_everybody_when_the_recorded_grouping_is_no_longer_a_grouping(): void
    {
        $user = User::factory()->create([
            'contact_list_grouping' => 'beets',
            'contact_list_sort' => 'added',
            'contact_list_direction' => 'desc',
        ]);
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertRedirect(route('contacts.index', [
                'vault' => $vault,
                'filter' => 'all',
                'sort' => 'added',
                'direction' => 'desc',
            ]));
    }

    #[Test]
    public function it_falls_back_to_the_name_when_the_address_asks_for_nonsense(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.index', [
            'vault' => $vault,
            'filter' => 'all',
            'sort' => 'shoe-size',
            'direction' => 'sideways',
        ]))->assertStatus(200);

        $user->refresh();

        $this->assertEquals('name', $user->contact_list_sort);
        $this->assertEquals('asc', $user->contact_list_direction);
    }

    #[Test]
    public function it_records_nothing_when_the_request_is_a_prefetch(): void
    {
        $user = User::factory()->create(['contact_list_grouping' => 'favourites']);
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)
            ->withHeaders(['Sec-Purpose' => 'prefetch'])
            ->get(route('contacts.index', ['vault' => $vault, 'filter' => 'names-only']))
            ->assertStatus(200);

        $this->assertEquals('favourites', $user->refresh()->contact_list_grouping);
    }

    #[Test]
    public function it_searches_the_name_and_the_nickname(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', ['nickname' => 'Prison Mike']);
        $this->contact($vault, 'Dwight', 'Schrute');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'query' => 'schrute']))
            ->assertSee('Dwight Schrute')
            ->assertDontSee('Michael Scott');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'query' => 'prison']))
            ->assertSee('Michael Scott')
            ->assertDontSee('Dwight Schrute');
    }

    #[Test]
    public function it_sorts_on_the_column_it_was_asked_for(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');
        $this->contact($vault, 'Dwight', 'Schrute');

        $ascending = $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))->getContent();
        $descending = $this->actingAs($user)
            ->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'sort' => 'name', 'direction' => 'desc']))
            ->getContent();

        $this->assertLessThan(
            mb_strpos((string) $ascending, 'Michael Scott'),
            mb_strpos((string) $ascending, 'Dwight Schrute'),
        );

        $this->assertLessThan(
            mb_strpos((string) $descending, 'Dwight Schrute'),
            mb_strpos((string) $descending, 'Michael Scott'),
        );
    }

    #[Test]
    public function it_shows_a_chip_for_every_way_of_filtering_the_list(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', ['is_favourite' => true]);
        $this->contact($vault, 'Dwight', 'Schrute');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertSee('Everybody')
            ->assertSee('Favourites')
            ->assertSee('Partial contacts')
            ->assertDontSee('Archived');
    }

    #[Test]
    public function it_pushes_the_people_it_knows_nothing_about_to_the_end_either_way(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', ['nickname' => 'Prison Mike']);
        $this->contact($vault, 'Dwight', 'Schrute');

        foreach (['asc', 'desc'] as $direction) {
            $html = (string) $this->actingAs($user)
                ->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'sort' => 'nickname', 'direction' => $direction]))
                ->getContent();

            $this->assertLessThan(
                mb_strpos($html, 'Dwight Schrute'),
                mb_strpos($html, 'Michael Scott'),
                "The contact with no nickname should come last, sorted {$direction}.",
            );
        }
    }

    #[Test]
    public function it_falls_back_to_the_name_when_the_url_asks_for_nonsense(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)
            ->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'sort' => 'shoe-size', 'direction' => 'sideways']))
            ->assertStatus(200)
            ->assertSee('Michael Scott')
            ->assertSee('aria-sort="ascending"', escape: false)
            ->assertSee('sort=name&amp;direction=desc', escape: false);
    }

    #[Test]
    public function it_does_not_answer_to_a_chip_it_never_offered(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(url("/vaults/{$vault->uuid}/contacts/beets"))->assertNotFound();
        $this->actingAs($user)->get(url("/vaults/{$vault->uuid}/contacts/beets/health"))->assertNotFound();
    }

    #[Test]
    public function either_spelling_of_an_address_reaches_the_page_of_one_person(): void
    {
        $vault = Vault::factory()->create();

        $this->assertSame('contacts.show', $this->routeNameFor("/vaults/{$vault->uuid}/contacts/".Str::uuid7()));
        $this->assertSame('contacts.show', $this->routeNameFor("/vaults/{$vault->slug}/contacts/jim-halpert"));
    }

    #[Test]
    public function it_does_not_find_a_uuid_nobody_owns(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(url("/vaults/{$vault->uuid}/contacts/".Str::uuid7()))->assertNotFound();
    }

    #[Test]
    public function it_still_reaches_the_form_that_sits_under_the_same_path(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertSee('First name');
    }

    #[Test]
    public function it_says_the_vault_is_empty_rather_than_showing_a_bare_table(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertSee('Nobody here yet')
            ->assertSee('This vault holds nobody so far.');
    }

    #[Test]
    public function it_says_where_the_partial_contacts_went_rather_than_claiming_the_vault_is_bare(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Bob', 'Vance', ['is_partial' => true]);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertSee('Nobody here yet')
            ->assertSee('One person here was written down as a partial contact')
            ->assertDontSee('This vault holds nobody so far.');
    }

    #[Test]
    public function it_warns_that_a_partial_contact_will_not_be_on_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'names' => [['kind' => 'written', 'given_name' => 'Bob']],
            'display' => 0,
            'is_partial' => '1',
        ])
            ->assertSessionHas('status', 'Bob is written down.')
            ->assertSessionHas('status_description', 'A partial contact stays off the list of contacts until they are more than a first name.');

        $this->actingAs($user)->post(route('contacts.create', $vault), ['names' => [['kind' => 'written', 'given_name' => 'Michael']], 'display' => 0])
            ->assertSessionHas('status_description', 'The rest of the page is yours to fill in whenever you know it.');
    }

    #[Test]
    public function it_says_so_when_the_search_matched_nobody(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'query' => 'Filippelli']))
            ->assertSee('Nothing matches')
            ->assertDontSee('Nobody here yet');
    }

    #[Test]
    public function it_offers_numbered_pages_and_a_choice_of_how_many_rows_a_page_holds(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        Contact::factory()->count(51)->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $vault->defaultTemplates()->sole()->structure_version_id,
        ]);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertSee('1 to 50 of 51')
            ->assertSee('aria-current="page"', escape: false)
            ->assertSee('Rows per page')
            ->assertSee(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'page' => 2]), escape: false)
            ->assertDontSee('Load more');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'size' => 10, 'page' => 2]))
            ->assertSee('11 to 20 of 51')
            ->assertSee('value="10" selected', escape: false)
            ->assertSee(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'size' => 10, 'page' => 3]));

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'size' => 7]))
            ->assertSee('1 to 50 of 51');
    }

    #[Test]
    public function it_remembers_the_rows_a_page_holds_for_the_next_visit(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        Contact::factory()->count(21)->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $vault->defaultTemplates()->sole()->structure_version_id,
        ]);

        $chosen = $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all', 'size' => 10]));

        $chosen->assertSee('1 to 10 of 21');
        $chosen->assertCookie(PageSize::COOKIE, json_encode(['contacts.index' => 10]));

        $this->actingAs($user)
            ->withCookie(PageSize::COOKIE, json_encode(['contacts.index' => 10]))
            ->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertSee('1 to 10 of 21')
            ->assertSee('value="10" selected', escape: false);
    }

    #[Test]
    public function it_shows_the_form_that_writes_somebody_down(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertSee('New contact')
            ->assertSee('First name')
            ->assertSee('Create the contact');
    }

    #[Test]
    public function it_leads_from_the_form_back_up_to_the_list_it_adds_to(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertSee('People')
            ->assertSee('Cancel')
            ->assertSee(route('contacts.index', $vault))
            ->assertSee(route('vaults.show', $vault));
    }

    #[Test]
    public function it_says_which_vault_and_where_in_it_at_the_top_of_every_screen(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');

        foreach ([route('contacts.index', ['vault' => $vault, 'filter' => 'all']), route('contacts.new', $vault)] as $url) {
            $this->actingAs($user)->get($url)
                ->assertStatus(200)
                ->assertSee($vault->name)
                ->assertSee(route('vaults.show', $vault));
        }
    }

    #[Test]
    public function it_marks_the_people_of_the_vault_as_where_you_are_in_the_column(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertSee('aria-current="page"', escape: false)
            ->assertSee('aria-label="This vault"', escape: false);
    }

    #[Test]
    public function it_keeps_the_box_that_opens_the_form_again_with_the_buttons(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertSee('Create another one after this')
            ->assertSee('form="contact-form"', escape: false);
    }

    #[Test]
    public function it_refuses_the_button_only_when_the_vault_has_nothing_to_start_a_contact_from(): void
    {
        $user = User::factory()->create();
        $blocked = Vault::factory()->create(['account_id' => $user->account_id]);
        $ready = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $blocked))
            ->assertStatus(200)
            ->assertSee('disabled="disabled"', escape: false);

        $this->actingAs($user)->get(route('contacts.new', $ready))
            ->assertStatus(200)
            ->assertDontSee('disabled="disabled"', escape: false);
    }

    #[Test]
    public function it_goes_back_to_the_empty_form_when_another_one_is_coming(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'names' => [['kind' => 'written', 'given_name' => 'Dwight']],
            'display' => 0,
            'keep_going' => '1',
        ])->assertRedirect(route('contacts.new', $vault));

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'names' => [['kind' => 'written', 'given_name' => 'Pam']],
            'display' => 0,
        ])->assertRedirect(route('contacts.show', [
            'vault' => $vault,
            'contact' => Contact::query()->where('first_name', 'Pam')->sole(),
        ]));
    }

    #[Test]
    public function it_says_the_contact_is_written_down_on_the_page_it_opens(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->followingRedirects()
            ->post(route('contacts.create', $vault), ['names' => [['kind' => 'written', 'given_name' => 'Michael', 'family_name' => 'Scott']], 'display' => 0])
            ->assertStatus(200)
            ->assertSee('Michael Scott is written down.')
            ->assertSee('The rest of the page is yours to fill in whenever you know it.');
    }

    #[Test]
    public function it_writes_the_contact_down_and_opens_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'names' => [['kind' => 'written', 'prefix' => 'Mr', 'given_name' => 'Michael', 'additional_name' => 'Gary', 'family_name' => 'Scott', 'suffix' => 'Jr', 'name_order' => NameOrderEnum::FamilyFirst->value]],
            'display' => 0,
            'nickname' => 'Prison Mike',
            'maiden_name' => '',
        ])
            ->assertRedirect(route('contacts.show', ['vault' => $vault, 'contact' => Contact::query()->sole()]))
            ->assertSessionHas('status', 'Scott Michael Gary is written down.');

        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'prefix' => 'Mr',
            'first_name' => 'Michael',
            'middle_name' => 'Gary',
            'last_name' => 'Scott',
            'suffix' => 'Jr',
            'nickname' => 'Prison Mike',
            'name_order' => NameOrderEnum::FamilyFirst->value,
            'is_partial' => false,
            'is_favourite' => false,
        ]);
    }

    #[Test]
    public function it_carries_the_two_flags_the_form_offers(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'names' => [['kind' => 'written', 'given_name' => 'Pam']],
            'display' => 0,
            'is_favourite' => '1',
            'is_partial' => '1',
        ]);

        $contact = Contact::query()->sole();

        $this->assertTrue($contact->is_favourite);
        $this->assertTrue($contact->is_partial);
    }

    #[Test]
    public function it_hands_back_the_empty_form_to_somebody_working_through_a_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'names' => [['kind' => 'written', 'given_name' => 'Dwight']],
            'display' => 0,
            'keep_going' => '1',
        ])->assertRedirect(route('contacts.new', $vault));

        $this->assertDatabaseHas('contacts', ['vault_id' => $vault->id, 'first_name' => 'Dwight']);
    }

    #[Test]
    public function it_starts_the_contact_on_the_template_that_was_asked_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $work = $this->publishedContactTemplate($user, 'Work contact');

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'names' => [['kind' => 'written', 'given_name' => 'Jan']],
            'display' => 0,
            'structure_version_id' => (string) $work->structure->published_version_id,
        ])->assertRedirect(route('contacts.show', ['vault' => $vault, 'contact' => Contact::query()->sole()]));

        $this->assertEquals($work->structure->published_version_id, Contact::query()->sole()->structure_version_id);
    }

    #[Test]
    public function it_refuses_a_contact_with_no_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), ['names' => [], 'nickname' => 'Scott'])
            ->assertSessionHasErrors('names');

        $this->assertEquals(0, Contact::query()->count());
    }

    #[Test]
    public function it_shows_what_was_typed_rather_than_reverting_a_rejected_form(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)
            ->from(route('contacts.new', $vault))
            ->post(route('contacts.create', $vault), [
                'names' => [['kind' => 'phonetic', 'formatted_name' => 'Scott']],
                'display' => 0,
                'nickname' => 'Prison Mike',
                'is_favourite' => '1',
            ])
            ->assertRedirect(route('contacts.new', $vault));

        $form = $this->actingAs($user)->get(route('contacts.new', $vault));

        $form->assertSee('Scott')
            ->assertSee('value="Prison Mike"', false);

        $this->assertMatchesRegularExpression(
            '/id="is_favourite".*?checked/s',
            (string) $form->getContent(),
        );
    }

    #[Test]
    public function it_will_not_start_a_contact_from_a_version_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $vault = $this->vaultReadyForContacts($michael);
        $elsewhere = $this->publishedContactTemplate($dwight, 'Beet varieties');

        $this->actingAs($michael)
            ->from(route('contacts.new', $vault))
            ->post(route('contacts.create', $vault), [
                'names' => [['kind' => 'written', 'given_name' => '']],
                'display' => 0,
                'structure_version_id' => $elsewhere->structure->published_version_id,
            ]);

        $this->actingAs($michael)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertDontSee('Beet varieties')
            ->assertSee('no template for new contacts to start from');
    }

    #[Test]
    public function it_refuses_a_template_belonging_to_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $vault = $this->vaultReadyForContacts($michael);
        $elsewhere = $this->publishedContactTemplate($dwight, 'Beet varieties');

        $this->actingAs($michael)->post(route('contacts.create', $vault), [
            'names' => [['kind' => 'written', 'given_name' => 'Michael']],
            'display' => 0,
            'structure_version_id' => $elsewhere->structure->published_version_id,
        ])->assertSessionHasErrors('structure_version_id');

        $this->assertEquals(0, Contact::query()->count());
    }

    #[Test]
    public function it_writes_down_that_the_contact_was_opened(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))->assertStatus(200);

        Queue::assertPushedOn('low', RecordContactVisit::class);
    }

    #[Test]
    public function it_offers_one_way_in_to_the_things_the_template_has_no_say_over(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('Edit contact')
            ->assertSee(route('contacts.edit', ['vault' => $vault, 'contact' => $michael]), false)
            ->assertDontSee('Remind me')
            ->assertDontSee('Change the name');
    }

    #[Test]
    public function it_keeps_the_share_the_merge_and_the_card_behind_one_menu(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('aria-label="More actions for Michael Scott"', escape: false)
            ->assertSee('aria-haspopup="menu"', escape: false)
            ->assertSee('role="menu"', escape: false)
            ->assertSee('role="menuitem"', escape: false)
            ->assertSee('Share')
            ->assertSee(route('contacts.shareLinks.index', ['vault' => $vault, 'contact' => $michael]), false)
            ->assertSee('Export as vCard')
            ->assertSee(route('contacts.vcard.show', ['vault' => $vault, 'contact' => $michael]), false)
            ->assertSee('Merge')
            ->assertSee(route('contacts.merge.new', ['vault' => $vault, 'contact' => $michael]), false);
    }

    #[Test]
    public function the_record_offers_the_way_back_to_the_list_in_the_breadcrumb_and_the_sidebar_only(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $page = $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->getContent();

        $this->assertSame(2, substr_count($page, 'href="'.route('contacts.index', $vault).'"'));
    }

    #[Test]
    public function the_edit_screen_carries_the_name_and_the_way_out(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.edit', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('Change the name')
            ->assertSee('Michael', false)
            ->assertSee('Scott', false)
            ->assertSee('Delete contact')
            ->assertSee(route('contacts.destroy', ['vault' => $vault, 'contact' => $michael]), false);
    }

    #[Test]
    public function the_edit_screen_says_why_the_name_cannot_be_saved_while_it_is_being_changed(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.edit', ['vault' => $vault, 'contact' => $michael]))
            ->assertSee('@name-editor-busy.window="held = $event.detail"', false)
            ->assertSee(':aria-disabled="held"', false)
            ->assertSee('aria-describedby="name-form-held"', false)
            ->assertSee(__('Finish the change to the name first, or cancel it.'));
    }

    #[Test]
    public function the_form_says_why_the_contact_cannot_be_created_while_the_name_is_being_changed(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertSee(':aria-disabled="held"', false)
            ->assertSee('aria-describedby="contact-form-held"', false)
            ->assertSee(__('Finish the change to the name first, or cancel it.'));
    }

    #[Test]
    public function it_names_the_field_of_a_refused_name_in_plain_words(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)
            ->post(route('contacts.create', $vault), [
                'names' => [['kind' => 'phonetic', 'given_name' => 'Maikeru']],
                'display' => 0,
            ])
            ->assertSessionHasErrors('names.0.phonetic_system');

        $message = session('errors')->first('names.0.phonetic_system');

        $this->assertStringContainsString(__('pronunciation system'), $message);
        $this->assertStringNotContainsString('names.0', $message);
    }

    #[Test]
    public function the_edit_screen_offers_a_picture_and_nothing_to_remove_until_there_is_one(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.edit', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('Photo')
            ->assertSee(route('contacts.avatar.update', ['vault' => $vault, 'contact' => $michael]), false)
            ->assertDontSee('Remove the photo');
    }

    #[Test]
    public function the_edit_screen_draws_the_picture_and_the_way_to_be_rid_of_it(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $file = File::factory()->image()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);
        $michael = $this->contact($vault, 'Michael', 'Scott', ['avatar_file_id' => $file->id]);

        $this->actingAs($user)->get(route('contacts.edit', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee(route('files.show', ['vault' => $vault, 'file' => $file]), false)
            ->assertSee('Remove the photo')
            ->assertSee('Remove the photo of Michael Scott?')
            ->assertSee('They are drawn as their initials again.')
            ->assertDontSee('@js(', false)
            ->assertSee(route('contacts.avatar.destroy', ['vault' => $vault, 'contact' => $michael]), false);
    }

    #[Test]
    public function the_edit_screen_offers_no_way_out_of_the_contact_that_represents_you(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $me = $this->contact($vault, 'Michael', 'Scott', ['is_me' => true]);

        $this->actingAs($user)->get(route('contacts.edit', ['vault' => $vault, 'contact' => $me]))
            ->assertStatus(200)
            ->assertSee('Change the name')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('This is the contact that represents you, and it stays.', false);
    }

    #[Test]
    public function the_edit_screen_of_any_other_contact_offers_the_way_out(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $dwight = $this->contact($vault, 'Dwight', 'Schrute');

        $this->actingAs($user)->get(route('contacts.edit', ['vault' => $vault, 'contact' => $dwight]))
            ->assertStatus(200)
            ->assertSee('Delete the contact')
            ->assertSee(route('contacts.destroy', ['vault' => $vault, 'contact' => $dwight]), false)
            ->assertSee('Taking them off the list can be undone.', false);
    }

    #[Test]
    public function it_deletes_a_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)
            ->delete(route('contacts.destroy', ['vault' => $vault, 'contact' => $michael]))
            ->assertRedirect(route('contacts.index', $vault))
            ->assertSessionHas('status');

        $this->assertSoftDeleted($michael);
    }

    #[Test]
    public function it_refuses_to_delete_the_contact_that_represents_you(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'is_me' => true,
        ]);

        $this->actingAs($user)
            ->delete(route('contacts.destroy', ['vault' => $vault, 'contact' => $michael]))
            ->assertRedirect(route('contacts.edit', ['vault' => $vault, 'contact' => $michael]))
            ->assertSessionHasErrors('contact');

        $this->assertNotSoftDeleted($michael);
    }

    #[Test]
    public function it_does_not_edit_or_delete_somebody_reached_through_the_wrong_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $elsewhere = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.edit', ['vault' => $elsewhere, 'contact' => $michael]))
            ->assertNotFound();

        $this->actingAs($user)->delete(route('contacts.destroy', ['vault' => $elsewhere, 'contact' => $michael]))
            ->assertNotFound();
    }

    #[Test]
    public function it_opens_the_page_of_one_person(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Someone in the family');
        $michael = $this->contact($vault, 'Michael', 'Scott');
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Dinner party at the condo']);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $michael->id]);

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('Michael Scott')
            ->assertSee('Overview')
            ->assertSee('Timeline')
            ->assertSee('Dinner party at the condo');
    }

    #[Test]
    public function it_answers_an_alpine_request_with_only_the_panels_it_targets(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Someone in the family');
        $michael = $this->contact($vault, 'Michael', 'Scott');
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Dinner party at the condo']);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $michael->id]);

        $this->actingAs($user)
            ->withHeaders(['X-Alpine-Request' => 'true', 'X-Alpine-Target' => 'contact-timeline notifications'])
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('Dinner party at the condo')
            ->assertSee('id="notifications"', escape: false)
            ->assertDontSee('Overview')
            ->assertDontSee('Edit contact');
    }

    #[Test]
    public function it_answers_an_alpine_request_with_the_whole_page_when_a_target_is_not_a_panel(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Someone in the family');
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)
            ->withHeaders(['X-Alpine-Request' => 'true', 'X-Alpine-Target' => 'contacts notifications'])
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('Overview')
            ->assertSee('Edit contact');
    }

    #[Test]
    public function it_asks_before_deleting_a_life_event_through_one_shared_card(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Someone in the family');
        $michael = $this->contact($vault, 'Michael', 'Scott');
        $dinner = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Dinner party at the condo']);
        $fire = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Fire drill']);
        LifeEventParticipant::factory()->create(['life_event_id' => $dinner->id, 'participant_id' => $michael->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $fire->id, 'participant_id' => $michael->id]);

        $response = $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertOk()
            ->assertSee('data-destroy-url="'.route('contacts.lifeEvents.destroy', ['vault' => $vault, 'contact' => $michael, 'lifeEvent' => $dinner]).'"', escape: false)
            ->assertSee('data-destroy-url="'.route('contacts.lifeEvents.destroy', ['vault' => $vault, 'contact' => $michael, 'lifeEvent' => $fire]).'"', escape: false)
            ->assertSee('It is removed from the timeline of Michael. The people stay.')
            ->assertSee('x-on:confirm-life-event-deletion.window', escape: false);

        $this->assertSame(1, substr_count($response->getContent(), 'aria-label="Delete this life event?"'));
    }

    #[Test]
    public function it_links_the_other_people_on_the_timeline_who_have_a_page(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Someone in the family');
        $michael = $this->contact($vault, 'Michael', 'Scott');
        $jan = $this->contact($vault, 'Jan', 'Levinson');
        $astrid = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Astrid', 'is_partial' => true]);
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Dinner party at the condo']);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $michael->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $jan->id, 'position' => 1]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $astrid->id, 'position' => 2]);

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertOk()
            ->assertSee('href="'.route('contacts.show', ['vault' => $vault, 'contact' => $jan]).'"', escape: false)
            ->assertDontSee('href="'.route('contacts.show', ['vault' => $vault, 'contact' => $astrid]).'"', escape: false)
            ->assertSee('Astrid');
    }

    #[Test]
    public function it_offers_to_record_a_life_event_from_the_page(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Someone in the family');
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertOk()
            ->assertSee('Add a life event')
            ->assertSee('x-data="lifeEventPanel(', escape: false)
            ->assertDontSee('aria-controls="life-event-panel"', escape: false)
            ->assertDontSee('Step 1 of 2 · Choose what happened')
            ->assertSee('Nothing on Michael’s timeline yet')
            ->assertDontSee('You added Michael Scott')
            ->assertDontSee('Write a note');
    }

    #[Test]
    public function it_opens_the_panel_on_the_type_chosen_when_the_life_event_came_back_refused(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Someone in the family');
        $michael = $this->contact($vault, 'Michael', 'Scott');
        $prank = $this->lifeEventTypeAsking($user, 'Prank', ['Victim' => FieldTypeEnum::Text]);

        $this->actingAs($user)
            ->from(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->post(route('contacts.lifeEvents.create', ['vault' => $vault, 'contact' => $michael]), [
                'life_event_type_id' => $prank->id,
            ])
            ->assertRedirect();

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertOk()
            ->assertSee('Victim')
            ->assertSee('\u0022step\u0022:\u0022details\u0022', escape: false);
    }

    #[Test]
    public function it_does_not_find_somebody_kept_in_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $elsewhere = $this->vaultReadyForContacts($user, 'Someone at work');
        $michael = $this->contact($elsewhere, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_the_relationships_page_of_another_account_or_another_vault(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $theirs = $this->vaultReadyForContacts($stranger);
        $michael = $this->contact($theirs, 'Michael', 'Scott');

        $mine = $this->vaultReadyForContacts($user);
        $jim = $this->contact($mine, 'Jim', 'Halpert');
        $elsewhere = $this->vaultReadyForContacts($user);

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $theirs, 'contact' => $michael, 'page' => 'relationships']))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $elsewhere, 'contact' => $jim, 'page' => 'relationships']))
            ->assertNotFound();
    }

    #[Test]
    public function the_version_history_names_every_version_the_record_has_been_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Friend');
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $first = $michael->structureVersion;
        $second = StructureVersion::factory()->published()->create([
            'structure_id' => $first->structure_id,
            'version_number' => 2,
            'change_note' => 'Ways to connect is its own section now.',
        ]);
        $first->structure->update(['published_version_id' => $second->id]);

        $this->actingAs($user)
            ->put(route('contacts.templateVersion.update', ['vault' => $vault, 'contact' => $michael]));

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'version-history']))
            ->assertStatus(200)
            ->assertSee('Friend, version 2')
            ->assertSee('Friend, version 1')
            ->assertSee('A template defines the sections and fields on this contact’s profile.')
            ->assertSee('Revert to Friend, version 1?')
            ->assertSee('name="structure_version_id" value="'.$first->id.'"', escape: false);
    }

    #[Test]
    public function the_version_history_offers_a_newer_version_like_every_other_page(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Friend');
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $second = StructureVersion::factory()->published()->create([
            'structure_id' => $michael->structureVersion->structure_id,
            'version_number' => 2,
        ]);
        $michael->structureVersion->structure->update(['published_version_id' => $second->id]);

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'version-history']))
            ->assertStatus(200)
            ->assertSee('Apply version 2');
    }

    #[Test]
    public function it_fills_a_section_in_in_the_same_number_of_queries_however_many_choices_it_offers(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $user->account->features()->active('marketplace');
        $user->account->features()->active('vcard-export');
        $user->account->features()->active('groups');
        $user->account->hasFullAccess();

        $few = $this->contactOnAVersionOf($user, $vault, sections: 1, choiceFields: 1);
        $many = $this->contactOnAVersionOf($user, $vault, sections: 1, choiceFields: 7);

        $this->assertSame(
            $this->queriesEditing($user, $vault, $few),
            $this->queriesEditing($user, $vault, $many),
            'Offering a choice must not cost a query per field.',
        );
    }

    #[Test]
    public function it_reads_a_record_in_the_same_number_of_queries_whatever_its_structure_holds(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $user->account->features()->active('marketplace');
        $user->account->features()->active('vcard-export');
        $user->account->features()->active('groups');
        $user->account->hasFullAccess();

        $small = $this->contactOnAVersionOf($user, $vault, sections: 2);
        $large = $this->contactOnAVersionOf($user, $vault, sections: 8);

        $this->assertSame(
            $this->queriesReading($user, $vault, $small),
            $this->queriesReading($user, $vault, $large),
            'Reading a record must not cost a query per section.',
        );
    }

    private function contactOnAVersionOf(User $user, Vault $vault, int $sections, int $choiceFields = 0): Contact
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $version->pages()->delete();

        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
        ]);

        foreach (range(1, $sections) as $position) {
            $section = Section::factory()->create(['account_id' => $user->account_id]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

            Field::factory()->count(3)->create(['section_version_id' => $sectionVersion->id]);

            foreach (range(1, $choiceFields) as $choice) {
                $list = ValueList::factory()->create(['account_id' => $user->account_id]);

                ValueListItem::factory()->count(4)->create([
                    'account_id' => $user->account_id,
                    'value_list_id' => $list->id,
                ]);

                Field::factory()->create([
                    'section_version_id' => $sectionVersion->id,
                    'type' => FieldTypeEnum::Select,
                    'value_list_id' => $list->id,
                    'position' => $choice + 3,
                ]);
            }

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position,
            ]);
        }

        $version->update(['status' => VersionStatusEnum::Published]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->refresh()->id,
        ]);
    }

    private function queriesEditing(User $user, Vault $vault, Contact $contact): int
    {
        $counted = 0;
        DB::listen(function () use (&$counted): void {
            $counted++;
        });

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $contact,
                'structureVersionSection' => $contact->structureVersion->assembly->first()->id,
            ]))
            ->assertStatus(200);

        return $counted;
    }

    private function queriesReading(User $user, Vault $vault, Contact $contact): int
    {
        $counted = 0;
        DB::listen(function () use (&$counted): void {
            $counted++;
        });

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $contact]))
            ->assertStatus(200);

        return $counted;
    }

    private function routeNameFor(string $path): ?string
    {
        $request = Request::create($path, 'GET');

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->matches($request)) {
                return $route->getName();
            }
        }

        return null;
    }

    #[Test]
    public function it_offers_the_star_to_a_contact_who_is_not_a_favourite(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('aria-pressed="false"', escape: false)
            ->assertSee('Add Michael Scott to favourites')
            ->assertSee(route('contacts.favourite.update', ['vault' => $vault, 'contact' => $michael]), escape: false)
            ->assertDontSee('Remove Michael Scott from favourites');
    }

    #[Test]
    public function it_shows_the_star_pressed_for_a_contact_who_is_a_favourite(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott', ['is_favourite' => true]);

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('aria-pressed="true"', escape: false)
            ->assertSee('Remove Michael Scott from favourites')
            ->assertSee('fill="currentColor"', escape: false)
            ->assertDontSee('Add Michael Scott to favourites');
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

    private function vaultReadyForContacts(User $user, string $name = 'Basic contact'): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, $name);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $template->structure->published_version_id,
        ]);

        return $vault;
    }

    private function publishedContactTemplate(User $user, string $name): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
        ]);

        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $template->structure->update(['published_version_id' => $version->id]);

        return $template;
    }

    #[Test]
    public function it_refuses_a_contact_past_the_ceiling_and_names_it(): void
    {
        Queue::fake();
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $response = $this->actingAs($user)
            ->from(route('contacts.new', ['vault' => $vault]))
            ->post(route('contacts.create', ['vault' => $vault]), ['names' => [['kind' => 'written', 'given_name' => 'Pam']], 'display' => 0]);

        $response->assertRedirect(route('contacts.new', ['vault' => $vault]));
        $response->assertSessionHasErrors('names');
        $this->assertSame(1, $user->account->contactCount());
    }

    #[Test]
    public function it_shows_the_offers_from_the_form_of_an_account_at_the_ceiling(): void
    {
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $response = $this->actingAs($user)->get(route('contacts.new', ['vault' => $vault]));

        $response->assertStatus(200);
        $response->assertSee('You are at the ceiling of the free plan', escape: false);
        $response->assertSee(route('settings.billing.index'), escape: false);
    }

    #[Test]
    public function it_opens_a_contact_at_its_readable_address(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
        ]);

        $this->assertSame('jim-halpert', $contact->slug);

        $this->actingAs($user)->get(url("/vaults/{$vault->slug}/contacts/jim-halpert"))->assertStatus(200);
        $this->actingAs($user)->get(url("/vaults/{$vault->uuid}/contacts/{$contact->uuid}"))->assertStatus(200);
    }

    #[Test]
    public function it_builds_the_address_of_a_contact_from_their_name(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
        ]);

        $address = route('contacts.show', ['vault' => $vault, 'contact' => $contact]);

        $this->assertStringContainsString("/vaults/{$vault->slug}/contacts/jim-halpert", $address);
    }

    #[Test]
    public function it_does_not_find_a_contact_through_the_wrong_vault_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $here = $this->vaultReadyForContacts($user);
        $elsewhere = $this->vaultReadyForContacts($user);
        $contact = Contact::factory()->create([
            'vault_id' => $here->id,
            'account_id' => $user->account_id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
        ]);

        $this->actingAs($user)->get(url("/vaults/{$elsewhere->slug}/contacts/{$contact->slug}"))->assertNotFound();
        $this->actingAs($user)->get(url("/vaults/{$elsewhere->slug}/contacts/{$contact->uuid}"))->assertNotFound();
    }

    #[Test]
    public function two_people_of_one_name_each_get_an_address_of_their_own(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $first = Contact::factory()->create([
            'vault_id' => $vault->id, 'account_id' => $user->account_id,
            'first_name' => 'Jim', 'last_name' => 'Halpert',
        ]);
        $second = Contact::factory()->create([
            'vault_id' => $vault->id, 'account_id' => $user->account_id,
            'first_name' => 'Jim', 'last_name' => 'Halpert',
        ]);

        $this->assertSame('jim-halpert', $first->slug);
        $this->assertSame('jim-halpert-2', $second->slug);

        $this->actingAs($user)->get(url("/vaults/{$vault->slug}/contacts/jim-halpert"))->assertStatus(200);
        $this->actingAs($user)->get(url("/vaults/{$vault->slug}/contacts/jim-halpert-2"))->assertStatus(200);
    }

    #[Test]
    public function it_opens_a_contact_named_in_another_alphabet_written_either_way(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'first_name' => '太郎',
            'last_name' => '田中',
            'name_order' => NameOrderEnum::FamilyFirst,
        ]);

        $this->assertSame('田中-太郎', $contact->slug);

        $this->actingAs($user)->get(url("/vaults/{$vault->slug}/contacts/田中-太郎"))->assertStatus(200);
        $this->actingAs($user)->get(url("/vaults/{$vault->slug}/contacts/".rawurlencode('田中-太郎')))->assertStatus(200);
    }

    #[Test]
    public function the_contacts_screen_marks_the_creation_unavailable_at_the_ceiling(): void
    {
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $this->contact($vault, 'Pam', 'Beesly');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertStatus(200)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('aria-describedby="contact-ceiling"', false)
            ->assertSee('Full access lifts the ceiling.', false);
    }

    #[Test]
    public function the_contacts_screen_offers_the_creation_below_the_ceiling(): void
    {
        config(['monica.free_contact_limit' => 30]);

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $this->contact($vault, 'Pam', 'Beesly');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertStatus(200)
            ->assertDontSee('aria-describedby="contact-ceiling"', false);
    }
}
