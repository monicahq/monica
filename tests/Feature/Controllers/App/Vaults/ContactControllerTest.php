<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\NameOrderEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Models\Contact;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
            ->assertSee('Sorted by name, ascending');
    }

    #[Test]
    public function it_does_not_answer_to_a_chip_it_never_offered(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(url("/vaults/{$vault->uuid}/contacts/beets"))->assertNotFound();
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
            ->assertSee('Back to the contacts')
            ->assertSee('Cancel')
            ->assertSee(route('contacts.index', $vault))
            ->assertSee(route('vaults.show', $vault));
    }

    #[Test]
    public function it_marks_the_people_of_the_vault_as_where_you_are_in_the_dock(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200)
            ->assertSee('aria-current="page"', escape: false)
            ->assertSee('erica-dock', escape: false);
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

    #[Test]
    public function it_starts_the_contact_on_the_template_that_was_asked_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $work = $this->publishedContactTemplate($user, 'Work contact');

        $this->actingAs($user)->post(route('contacts.create', $vault), [
            'first_name' => 'Jan',
            'template_version_id' => $work->published_version_id,
        ]);

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
