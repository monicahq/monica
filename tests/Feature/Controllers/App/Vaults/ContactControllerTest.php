<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\FieldTypeEnum;
use App\Enums\NameOrderEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
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

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertStatus(200)
            ->assertSee('Michael Scott')
            ->assertSee('Dwight Schrute')
            ->assertSee('Prison Mike')
            ->assertSee('Basic contact')
            ->assertSee('All 2 people in this vault, on a single list.');
    }

    #[Test]
    public function it_lists_nobody_from_another_vault(): void
    {
        $user = User::factory()->create();
        $scranton = $this->vaultReadyForContacts($user);
        $utica = $this->vaultReadyForContacts($user);

        $this->contact($scranton, 'Michael', 'Scott');
        $this->contact($utica, 'Karen', 'Filippelli');

        $this->actingAs($user)->get(route('contacts.index', $scranton))
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

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertSee('Michael Scott')
            ->assertDontSee('Dwight Schrute')
            ->assertDontSee('Bob Vance');
    }

    #[Test]
    public function it_shows_the_archived_people_and_nobody_else_under_their_own_chip(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');
        $this->contact($vault, 'Dwight', 'Schrute', ['is_archived' => true]);

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'archived']))
            ->assertSee('Dwight Schrute')
            ->assertDontSee('Michael Scott')
            ->assertSee('1 of 1 shown.');
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
    public function it_searches_the_name_and_the_nickname(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', ['nickname' => 'Prison Mike']);
        $this->contact($vault, 'Dwight', 'Schrute');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'query' => 'schrute']))
            ->assertSee('Dwight Schrute')
            ->assertDontSee('Michael Scott');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'query' => 'prison']))
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

        $ascending = $this->actingAs($user)->get(route('contacts.index', $vault))->getContent();
        $descending = $this->actingAs($user)
            ->get(route('contacts.index', ['vault' => $vault, 'sort' => 'name', 'direction' => 'desc']))
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
    public function it_draws_the_letters_only_over_a_list_that_is_in_name_order(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertSee('>S</h2>', escape: false);

        $content = str($this->actingAs($user)
            ->get(route('contacts.index', ['vault' => $vault, 'sort' => 'added', 'direction' => 'desc']))
            ->assertSee('Michael Scott')
            ->getContent())->after('</header>')->before('</main>')->toString();

        $this->assertStringNotContainsString('</h2>', $content);
    }

    #[Test]
    public function it_shows_a_chip_for_every_way_of_filtering_the_list(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', ['is_favourite' => true]);
        $this->contact($vault, 'Dwight', 'Schrute');

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertSee('Everybody')
            ->assertSee('Favourites')
            ->assertSee('Archived');
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
                ->get(route('contacts.index', ['vault' => $vault, 'sort' => 'nickname', 'direction' => $direction]))
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
            ->get(route('contacts.index', ['vault' => $vault, 'sort' => 'shoe-size', 'direction' => 'sideways']))
            ->assertStatus(200)
            ->assertSee('Michael Scott')
            ->assertSee('<option value="name" selected>Name</option>', escape: false)
            ->assertSee('<option value="asc" selected>A to Z</option>', escape: false);
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

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertSee('Nobody here yet')
            ->assertSee('This vault holds nobody so far.');
    }

    #[Test]
    public function it_says_where_the_partial_contacts_went_rather_than_claiming_the_vault_is_bare(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Bob', 'Vance', ['is_partial' => true]);

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertSee('Nobody here yet')
            ->assertSee('One person here was written down as a partial contact')
            ->assertDontSee('This vault holds nobody so far.');
    }

    #[Test]
    public function it_warns_that_a_partial_contact_will_not_be_on_the_list_it_lands_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'first_name' => 'Bob',
            'is_partial' => '1',
        ])
            ->assertRedirect(route('contacts.index', $vault))
            ->assertSessionHas('status_description', 'A partial contact stays off the list of contacts until they are more than a first name.');

        $this->actingAs($user)->post(route('contacts.create', $vault), ['first_name' => 'Michael'])
            ->assertSessionHas('status_description', 'The rest of the page is yours to fill in whenever you know it.');
    }

    #[Test]
    public function it_says_so_when_the_search_matched_nobody(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'query' => 'Filippelli']))
            ->assertSee('Nothing matches')
            ->assertDontSee('Nobody here yet');
    }

    #[Test]
    public function it_offers_the_next_page_and_appends_it(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        Contact::factory()->count(51)->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $vault->defaultTemplates()->sole()->structure_version_id,
        ]);

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertSee('Load more')
            ->assertDontSee('End of the list.');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'page' => 2]))
            ->assertDontSee('Load more')
            ->assertSee('End of the list. 51 people, no pages.');
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

        foreach ([route('contacts.index', $vault), route('contacts.new', $vault)] as $url) {
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
    public function it_offers_the_template_only_when_there_is_more_than_one(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertDontSee('name="structure_version_id"', false);

        $this->publishedContactTemplate($user, 'Work contact');

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertSee('name="structure_version_id"', false)
            ->assertSee('Work contact');
    }

    #[Test]
    public function it_says_nothing_about_itself_while_it_can_do_its_job(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Standard contact');

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertDontSee('Standard contact')
            ->assertDontSee('no template for new contacts to start from');
    }

    #[Test]
    public function it_says_so_when_the_vault_has_nothing_to_start_a_contact_from(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertSee('This vault has no template for new contacts to start from.');
    }

    #[Test]
    public function it_writes_the_contact_down_and_lands_on_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'prefix' => 'Mr',
            'first_name' => 'Michael',
            'middle_name' => 'Gary',
            'last_name' => 'Scott',
            'suffix' => 'Jr',
            'nickname' => 'Prison Mike',
            'maiden_name' => '',
            'name_order' => NameOrderEnum::FamilyFirst->value,
        ])
            ->assertRedirect(route('contacts.index', $vault))
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
            'first_name' => 'Pam',
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
            'first_name' => 'Dwight',
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
            'first_name' => 'Jan',
            'structure_version_id' => (string) $work->structure->published_version_id,
        ])->assertRedirect(route('contacts.index', $vault));

        $this->assertEquals($work->structure->published_version_id, Contact::query()->sole()->structure_version_id);
    }

    #[Test]
    public function it_refuses_a_contact_with_no_first_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault), ['last_name' => 'Scott'])
            ->assertSessionHasErrors('first_name');

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
                'first_name' => '',
                'last_name' => 'Scott',
                'nickname' => 'Prison Mike',
                'is_favourite' => '1',
            ])
            ->assertRedirect(route('contacts.new', $vault));

        $form = $this->actingAs($user)->get(route('contacts.new', $vault));

        $form->assertSee('value="Scott"', false)
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
                'first_name' => '',
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
            'first_name' => 'Michael',
            'structure_version_id' => $elsewhere->structure->published_version_id,
        ])->assertSessionHasErrors('structure_version_id');

        $this->assertEquals(0, Contact::query()->count());
    }

    #[Test]
    public function it_does_not_find_the_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $elsewhere = Vault::factory()->create();

        $this->actingAs($user)->get(route('contacts.new', $elsewhere))->assertNotFound();
        $this->actingAs($user)->post(route('contacts.create', $elsewhere), ['first_name' => 'Michael'])
            ->assertNotFound();
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
    public function it_keeps_the_card_and_the_merge_behind_one_menu(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('x-data="{ menuOpen: false }"', escape: false)
            ->assertSee('@click="menuOpen = ! menuOpen"', escape: false)
            ->assertSee('aria-haspopup="menu"', escape: false)
            ->assertSee('role="menu"', escape: false)
            ->assertSee('role="menuitem"', escape: false)
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
    public function the_record_draws_the_picture_of_the_contact(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $file = File::factory()->image()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);
        $pam = $this->contact($vault, 'Pam', 'Beesly', ['avatar_file_id' => $file->id]);

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $pam]))
            ->assertStatus(200)
            ->assertSee(route('files.show', ['vault' => $vault, 'file' => $file]), false);
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
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $me = $this->contact($vault, 'Michael', 'Scott', ['is_me' => true]);

        $this->actingAs($user)
            ->delete(route('contacts.destroy', ['vault' => $vault, 'contact' => $me]))
            ->assertRedirect(route('contacts.edit', ['vault' => $vault, 'contact' => $me]))
            ->assertSessionHasErrors('contact');

        $this->assertNotSoftDeleted($me);
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
    public function it_does_not_edit_or_delete_somebody_in_another_account(): void
    {
        Queue::fake();

        $stranger = User::factory()->create();
        $theirs = $this->vaultReadyForContacts($stranger);
        $michael = $this->contact($theirs, 'Michael', 'Scott');

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('contacts.edit', ['vault' => $theirs, 'contact' => $michael]))
            ->assertNotFound();

        $this->actingAs($user)->delete(route('contacts.destroy', ['vault' => $theirs, 'contact' => $michael]))
            ->assertNotFound();
    }

    #[Test]
    public function it_opens_the_page_of_one_person(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user, 'Someone in the family');
        $michael = $this->contact($vault, 'Michael', 'Scott', ['nickname' => 'Prison Mike']);

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('Michael Scott')
            ->assertSee('Prison Mike')
            ->assertSee('Overview')
            ->assertSee('History');
    }

    #[Test]
    public function it_shows_the_sections_of_the_page_the_address_names(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithAPage($user, 'Health', 'Ongoing', 'Knee');
        $michael->update(['structure_version_id' => $version->id]);

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'health']))
            ->assertStatus(200)
            ->assertSee('Ongoing')
            ->assertSee('Knee')
            ->assertDontSee('History');
    }

    #[Test]
    public function it_draws_no_control_until_a_block_is_opened(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithAPage($user, 'Health', 'Ongoing', 'Knee');
        $michael->update(['structure_version_id' => $version->id]);
        $field = Field::query()->where('label', 'Knee')->firstOrFail();
        $section = StructureVersionSection::query()->where('structure_version_id', $version->id)->sole();

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'health']))
            ->assertStatus(200)
            ->assertDontSee('name="fields['.$field->id.']"', false)
            ->assertSee(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => $section,
            ]), false);
    }

    #[Test]
    public function it_shows_what_was_written_in_a_field(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithAPage($user, 'Health', 'Ongoing', 'Knee');
        $michael->update(['structure_version_id' => $version->id]);
        $field = Field::query()->where('label', 'Knee')->firstOrFail();

        FieldValue::factory()->create([
            'account_id' => $michael->account_id,
            'vault_id' => $michael->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $michael->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => 'Twisted it skiing',
        ]);

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'health']))
            ->assertStatus(200)
            ->assertSee('Twisted it skiing', false);
    }

    #[Test]
    public function a_page_the_version_does_not_have_opens_the_overview(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'schrute-farms']))
            ->assertStatus(200)
            ->assertSee('History');
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
    public function it_does_not_find_somebody_in_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $theirs = $this->vaultReadyForContacts($stranger);
        $michael = $this->contact($theirs, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $theirs, 'contact' => $michael]))
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
    public function the_record_offers_its_version_history_as_a_tab(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('Version history')
            ->assertSee(route('contacts.show', [
                'vault' => $vault,
                'contact' => $michael,
                'page' => 'version-history',
            ]), false);
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
            ->assertSee('On this version')
            ->assertSee('First template')
            ->assertSee('applied by you')
            ->assertSee('chosen when they were written down')
            ->assertSee('Ways to connect is its own section now.')
            ->assertSee('Move to this version')
            ->assertSee('A version only decides what this page shows.');
    }

    #[Test]
    public function the_version_history_keeps_the_banner_off_its_own_tab(): void
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
            ->assertDontSee('Version 2 of the Friend template is ready')
            ->assertSee('Version 2 exists and this contact is not on it.')
            ->assertSee('Apply version 2');
    }

    #[Test]
    public function it_fills_a_section_in_in_the_same_number_of_queries_however_many_choices_it_offers(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

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
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $small = $this->contactOnAVersionOf($user, $vault, sections: 2);
        $large = $this->contactOnAVersionOf($user, $vault, sections: 8);

        $this->assertSame(
            $this->queriesReading($user, $vault, $small),
            $this->queriesReading($user, $vault, $large),
            'Reading a record must not cost a query per section.',
        );
    }

    private function versionWithAPage(User $user, string $pageLabel, string $sectionName, string $fieldLabel): StructureVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => Str::slug($pageLabel),
            'label' => $pageLabel,
        ]);

        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $sectionName]);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => $fieldLabel]);

        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $page->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->update(['status' => VersionStatusEnum::Published]);

        return $version->refresh();
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

        foreach (Route::getRoutes() as $route) {
            if ($route->matches($request)) {
                return $route->getName();
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $attributes */
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
    public function it_finds_a_name_typed_without_its_accent(): void
    {
        $user = User::factory()->create(['locale' => 'fr_FR']);
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Dwight', 'Élodie');
        $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'query' => 'elodie']))
            ->assertStatus(200)
            ->assertSee('Dwight Élodie')
            ->assertDontSee('Michael Scott');
    }

    #[Test]
    public function it_says_so_when_a_search_matches_nobody(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'query' => 'Bob Vance of Vance Refrigeration']))
            ->assertStatus(200)
            ->assertSee('Nothing matches')
            ->assertDontSee('Michael Scott');
    }

    #[Test]
    public function it_files_a_name_under_the_letter_the_readers_language_files_it_under(): void
    {
        $user = User::factory()->create(['locale' => 'fr_FR']);
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Dwight', 'Élodie');

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertStatus(200)
            ->assertSee('Dwight Élodie');
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
            ->post(route('contacts.create', ['vault' => $vault]), ['first_name' => 'Pam']);

        $response->assertRedirect(route('contacts.new', ['vault' => $vault]));
        $response->assertSessionHasErrors('first_name');
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
        $response->assertSee('You are at the ceiling of the free offer', escape: false);
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
    public function it_does_not_find_the_readable_address_of_another_account(): void
    {
        $user = User::factory()->create();
        $theirs = Contact::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);

        $this->actingAs($user)
            ->get(url("/vaults/{$theirs->vault->slug}/contacts/{$theirs->slug}"))
            ->assertNotFound();
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

        $this->actingAs($user)->get(route('contacts.index', $vault))
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

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertStatus(200)
            ->assertDontSee('aria-describedby="contact-ceiling"', false);
    }
}
