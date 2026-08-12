<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\NameOrderEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
            ->assertSee('All 2 people in this vault, on one scrolling list.');
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

        $this->actingAs($user)
            ->get(route('contacts.index', ['vault' => $vault, 'sort' => 'added', 'direction' => 'desc']))
            ->assertSee('Michael Scott')
            ->assertDontSee('</h2>', escape: false);
    }

    #[Test]
    public function it_says_how_many_people_each_chip_would_leave(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->contact($vault, 'Michael', 'Scott', ['is_favourite' => true]);
        $this->contact($vault, 'Dwight', 'Schrute');

        $this->actingAs($user)->get(route('contacts.index', $vault))
            ->assertSee('Everyone')
            ->assertSee('Favourites')
            ->assertSee('Archived')
            ->assertSee('<span class="font-mono text-[12px] opacity-70">2</span>', escape: false);
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

    /**
     * The page of one person sits under the same path, so a word the list does
     * not answer to has to stop before it is taken for somebody's uuid. Postgres
     * refuses to compare a uuid column against a word at all, so without the
     * constraint on the route this is a 500 rather than a 404.
     */
    #[Test]
    public function it_does_not_answer_to_a_chip_it_never_offered(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(url("/vaults/{$vault->uuid}/contacts/beets"))->assertNotFound();
        $this->actingAs($user)->get(url("/vaults/{$vault->uuid}/contacts/beets/health"))->assertNotFound();
    }

    #[Test]
    public function only_a_uuid_reaches_the_page_of_one_person(): void
    {
        $vault = Vault::factory()->create();

        $this->assertNull($this->routeNameFor("/vaults/{$vault->uuid}/contacts/beets"));
        $this->assertSame('contacts.show', $this->routeNameFor("/vaults/{$vault->uuid}/contacts/".Str::uuid7()));
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
            'template_version_id' => $vault->defaultTemplates()->sole()->template_version_id,
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
                ->assertSee(route('vaults.show', $vault))
                ->assertSee('Search people, notes, anything', escape: false);
        }
    }

    #[Test]
    public function it_marks_the_people_of_the_vault_as_where_you_are_in_the_dock(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertSee('aria-current="page"', escape: false)
            ->assertSee('aria-label="Main"', escape: false);
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
            ->assertDontSee('name="template_version_id"', false);

        $this->publishedContactTemplate($user, 'Work contact');

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertSee('name="template_version_id"', false)
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

    /**
     * The chosen version arrives as a string, which is what a select sends and
     * what the `integer` rule lets through without converting.
     */
    #[Test]
    public function it_starts_the_contact_on_the_template_that_was_asked_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $work = $this->publishedContactTemplate($user, 'Work contact');

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'first_name' => 'Jan',
            'template_version_id' => (string) $work->published_version_id,
        ])->assertRedirect(route('contacts.index', $vault));

        $this->assertEquals($work->published_version_id, Contact::query()->sole()->template_version_id);
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
                'template_version_id' => $elsewhere->published_version_id,
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
            'template_version_id' => $elsewhere->published_version_id,
        ])->assertSessionHasErrors('template_version_id');

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

    /**
     * The contact representing whoever is looking is renamed like any other.
     * What it cannot do is disappear, so the way out is not drawn for it.
     */
    #[Test]
    public function the_edit_screen_offers_no_way_out_of_the_contact_that_represents_you(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $me = $this->contact($vault, 'Michael', 'Scott', ['is_me' => true]);

        $this->actingAs($user)->get(route('contacts.edit', ['vault' => $vault, 'contact' => $me]))
            ->assertStatus(200)
            ->assertSee('Change the name')
            ->assertDontSee('Delete the contact')
            ->assertSee('This is the contact that represents you, and it stays.', false);
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
        $michael->update(['template_version_id' => $version->id]);

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'health']))
            ->assertStatus(200)
            ->assertSee('Ongoing')
            ->assertSee('Knee')
            ->assertDontSee('History');
    }

    /**
     * The page reads until somebody asks for a block of it. What it offers is
     * therefore a way in rather than a control, and no control at all.
     */
    #[Test]
    public function it_draws_no_control_until_a_block_is_opened(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithAPage($user, 'Health', 'Ongoing', 'Knee');
        $michael->update(['template_version_id' => $version->id]);
        $field = Field::query()->where('label', 'Knee')->firstOrFail();
        $section = TemplateVersionSection::query()->where('template_version_id', $version->id)->sole();

        $this->actingAs($user)->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'health']))
            ->assertStatus(200)
            ->assertDontSee('name="fields['.$field->id.']"', false)
            ->assertSee(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $section,
            ]), false);
    }

    #[Test]
    public function it_shows_what_was_written_in_a_field(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithAPage($user, 'Health', 'Ongoing', 'Knee');
        $michael->update(['template_version_id' => $version->id]);
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

    /**
     * A published version carrying one page, one section on it and one field
     * inside that. It is assembled while the version is still a draft, because
     * a published one is frozen and refuses every one of these rows.
     */
    private function versionWithAPage(User $user, string $pageLabel, string $sectionName, string $fieldLabel): TemplateVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => Str::slug($pageLabel),
            'label' => $pageLabel,
        ]);

        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $sectionName]);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => $fieldLabel]);

        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'template_version_page_id' => $page->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->update(['status' => VersionStatusEnum::Published]);

        return $version->refresh();
    }

    /**
     * Which route a path lands on, or null when none of them takes it. It is
     * asked here rather than through a request, because the answer has to be
     * the same on a database that would have tolerated the lookup.
     */
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

    private function vaultReadyForContacts(User $user, string $name = 'Basic contact'): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, $name);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => TemplateAppliesToEnum::Contact,
            'template_version_id' => $template->published_version_id,
        ]);

        return $vault;
    }

    private function publishedContactTemplate(User $user, string $name): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
        ]);

        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $template->published_version_id = $version->id;
        $template->save();

        return $template;
    }
}
