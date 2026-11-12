<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\FieldTypeEnum;
use App\Enums\NameOrderEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\RecordContactVisit;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
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
use Illuminate\Support\Facades\DB;
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

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertStatus(200)
            ->assertSee('Michael Scott')
            ->assertSee('Dwight Schrute')
            ->assertSee(route('contactListColumns.edit', $vault))
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
    public function it_does_not_answer_to_a_chip_it_never_offered(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(url("/vaults/{$vault->uuid}/contacts/beets"))->assertNotFound();
        $this->actingAs($user)->get(url("/vaults/{$vault->uuid}/contacts/beets/health"))->assertNotFound();
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
            ->assertSessionHas('status')
            ->assertSessionHas('status_description');

        $this->actingAs($user)->post(route('contacts.create', $vault), ['names' => [['kind' => 'written', 'given_name' => 'Michael']], 'display' => 0])
            ->assertSessionHas('status_description');
    }

    #[Test]
    public function it_shows_the_form_that_writes_somebody_down(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertStatus(200);
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
            ->assertSessionHas('status');

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
    public function the_edit_screen_carries_the_name_and_the_way_out(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $this->actingAs($user)->get(route('contacts.edit', ['vault' => $vault, 'contact' => $michael]))
            ->assertStatus(200)
            ->assertSee('Michael', false)
            ->assertSee('Scott', false)
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
            ->assertSee('aria-describedby="name-form-held"', false);
    }

    #[Test]
    public function the_form_says_why_the_contact_cannot_be_created_while_the_name_is_being_changed(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->get(route('contacts.new', $vault))
            ->assertSee(':aria-disabled="held"', false)
            ->assertSee('aria-describedby="contact-form-held"', false);
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

        $this->assertStringNotContainsString('names.0', $message);
    }

    #[Test]
    public function the_edit_screen_offers_no_way_out_of_the_contact_that_represents_you(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $me = $this->contact($vault, 'Michael', 'Scott', ['is_me' => true]);

        $this->actingAs($user)->get(route('contacts.edit', ['vault' => $vault, 'contact' => $me]))
            ->assertStatus(200)
            ->assertSee('aria-disabled="true"', false);
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
            ->assertSee('id="notifications"', escape: false);
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
            ->assertStatus(200);
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
    public function the_contacts_screen_marks_the_creation_unavailable_at_the_ceiling(): void
    {
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $this->contact($vault, 'Pam', 'Beesly');

        $this->actingAs($user)->get(route('contacts.index', ['vault' => $vault, 'filter' => 'all']))
            ->assertStatus(200)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('aria-describedby="contact-ceiling"', false);
    }
}
