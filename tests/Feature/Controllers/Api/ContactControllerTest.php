<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\ContactListColumnSourceEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\NameOrderEnum;
use App\Enums\ValueListEnum;
use App\Jobs\RecordContactVisit;
use App\Models\Contact;
use App\Models\ContactListColumn;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'name',
                'prefix',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'maiden_name',
                'nickname',
                'name_order',
                'initials',
                'avatar_colour',
                'avatar',
                'is_favourite',
                'is_archived',
                'is_partial',
                'is_me',
                'structure_version_id',
                'template_name',
                'categories',
                'last_life_event_at',
                'last_consulted_at',
                'created_at',
                'updated_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_lists_the_contacts_of_a_vault_in_name_order(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.attributes.name', 'Pam Beesly');
        $response->assertJsonPath('data.1.attributes.name', 'Michael Scott');
    }

    #[Test]
    public function it_lists_each_contact_with_the_columns_the_vault_shows(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Andy', 'nickname' => 'Nard Dog']);
        ContactListColumn::factory()->create(['vault_id' => $vault->id, 'source' => ContactListColumnSourceEnum::Nickname, 'position' => 1]);
        ContactListColumn::factory()->create(['vault_id' => $vault->id, 'source' => ContactListColumnSourceEnum::Email, 'is_visible' => false, 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts");

        $response->assertOk();
        $response->assertJsonStructure(['data' => [['columns' => [['source', 'section', 'field_key', 'values']]]]]);
        $response->assertJsonCount(1, 'data.0.columns');
        $response->assertJsonPath('data.0.columns.0.source', 'nickname');
        $response->assertJsonPath('data.0.columns.0.values', ['Nard Dog']);
    }

    #[Test]
    public function it_describes_a_contact_the_way_the_screen_shows_them(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $file = File::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'mime_type' => 'image/webp']);
        $contact = Contact::factory()->favourite()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'prefix' => 'Mr',
            'first_name' => 'Dwight',
            'middle_name' => 'Kurt',
            'last_name' => 'Schrute',
            'nickname' => 'Beet Boy',
            'name_order' => NameOrderEnum::FamilyFirst,
            'avatar_file_id' => $file->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'contact');
        $response->assertJsonPath('data.id', $contact->uuid);
        $response->assertJsonPath('data.attributes.name', 'Schrute Dwight Kurt');
        $response->assertJsonPath('data.attributes.prefix', 'Mr');
        $response->assertJsonPath('data.attributes.nickname', 'Beet Boy');
        $response->assertJsonPath('data.attributes.name_order', 'family_first');
        $response->assertJsonPath('data.attributes.initials', 'DS');
        $response->assertJsonPath('data.attributes.is_favourite', true);
        $response->assertJsonPath('data.attributes.avatar.id', $file->uuid);
    }

    #[Test]
    public function it_lists_only_the_favourites_when_asked_for_them(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->favourite()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'last_name' => 'Halpert']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'last_name' => 'Beesly']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?filter=favourites");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.last_name', 'Halpert');
    }

    #[Test]
    public function it_keeps_the_archived_out_of_every_grouping(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->archived()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'last_name' => 'Scott']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'last_name' => 'Beesly']);

        Sanctum::actingAs($user);

        $everyone = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts");

        $everyone->assertJsonCount(1, 'data');
        $everyone->assertJsonPath('data.0.attributes.last_name', 'Beesly');

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?filter=names-only")->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_keeps_a_partial_contact_off_every_grouping(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->partial()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Jack']);

        Sanctum::actingAs($user);

        foreach (['all', 'favourites'] as $grouping) {
            $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?filter={$grouping}")->assertJsonCount(0, 'data');
        }
    }

    #[Test]
    public function it_searches_the_name_ignoring_what_the_language_calls_a_minor_difference(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Élodie', 'last_name' => 'Halpert']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael', 'last_name' => 'Scott']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?query=elodie");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.first_name', 'Élodie');
    }

    #[Test]
    public function it_searches_the_nickname_as_it_was_typed(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'last_name' => 'Schrute', 'nickname' => 'Beet Boy']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'last_name' => 'Scott', 'nickname' => null]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?query=BEET");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.nickname', 'Beet Boy');
    }

    #[Test]
    public function it_answers_in_each_of_the_orders_the_screen_offers(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create([
            'vault_id' => $vault->id, 'account_id' => $user->account_id,
            'last_name' => 'Beesly', 'nickname' => 'Zed',
            'created_at' => CarbonImmutable::parse('2026-01-01'),
        ]);
        Contact::factory()->create([
            'vault_id' => $vault->id, 'account_id' => $user->account_id,
            'last_name' => 'Scott', 'nickname' => 'Ace',
            'created_at' => CarbonImmutable::parse('2026-02-01'),
        ]);

        Sanctum::actingAs($user);

        $byName = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?sort=name&direction=desc");
        $byNickname = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?sort=nickname");
        $byAdded = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?sort=added&direction=desc");

        $byName->assertJsonPath('data.0.attributes.last_name', 'Scott');
        $byNickname->assertJsonPath('data.0.attributes.nickname', 'Ace');
        $byAdded->assertJsonPath('data.0.attributes.last_name', 'Scott');
    }

    #[Test]
    public function it_puts_the_contacts_a_sort_knows_nothing_about_at_the_end(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'last_name' => 'Beesly', 'last_interaction_at' => null]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'last_name' => 'Scott', 'last_interaction_at' => CarbonImmutable::parse('2026-01-01')]);

        Sanctum::actingAs($user);

        $ascending = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?sort=last-spoke&direction=asc");
        $descending = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?sort=last-spoke&direction=desc");

        $ascending->assertJsonPath('data.1.attributes.last_name', 'Beesly');
        $descending->assertJsonPath('data.1.attributes.last_name', 'Beesly');
    }

    #[Test]
    public function it_refuses_a_grouping_a_sort_or_a_direction_it_does_not_offer(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?filter=everybody&sort=height&direction=sideways");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['filter', 'sort', 'direction']);
    }

    #[Test]
    public function it_lists_only_the_people_holding_any_of_the_categories_asked_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $user->account_id]);
        $sales = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Sales']);
        $field = Field::factory()->create(['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Jim']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Toby']);
        FieldValue::factory()->create(['valuable_id' => $jim->id, 'field_id' => $field->id, 'value_string' => null, 'value_list_item_ids' => [$sales->id]]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?categories[]={$sales->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.first_name', 'Jim');
        $response->assertJsonPath('data.0.attributes.categories', [['id' => $sales->id, 'label' => 'Sales']]);
    }

    #[Test]
    public function it_refuses_a_category_outside_the_accounts_list_of_categories(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $user->account_id]);
        $elsewhere = ValueListItem::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?categories[]={$elsewhere->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('categories.0');
    }

    #[Test]
    public function it_does_not_list_the_contacts_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_reached_through_the_wrong_vault_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$other->uuid}/contacts/{$contact->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_writes_a_contact_down_from_a_first_name_alone(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts", ['first_name' => 'Pam']);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.name', 'Pam');
        $response->assertJsonPath('data.attributes.avatar', null);
        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'first_name' => 'Pam',
            'structure_version_id' => $vault->defaultTemplates()->sole()->structure_version_id,
        ]);
    }

    #[Test]
    public function it_keeps_every_part_of_a_name_it_is_given_and_writes_it_in_the_order_asked_for(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts", [
            'prefix' => 'Mr',
            'first_name' => 'Dwight',
            'middle_name' => 'Kurt',
            'last_name' => 'Schrute',
            'suffix' => 'Jr',
            'maiden_name' => 'Beet',
            'nickname' => 'Beet Boy',
            'name_order' => 'family_first',
            'is_favourite' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.name', 'Schrute Dwight Kurt');
        $response->assertJsonPath('data.attributes.prefix', 'Mr');
        $response->assertJsonPath('data.attributes.suffix', 'Jr');
        $response->assertJsonPath('data.attributes.maiden_name', 'Beet');
        $response->assertJsonPath('data.attributes.nickname', 'Beet Boy');
        $response->assertJsonPath('data.attributes.is_favourite', true);
    }

    #[Test]
    public function it_writes_a_contact_down_on_the_structure_it_is_told_to(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $other = $this->publishedContactTemplate($user, 'Colleague');

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts", [
            'first_name' => 'Pam',
            'structure_version_id' => $other->structure->published_version_id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.structure_version_id', $other->structure->published_version_id);
        $response->assertJsonPath('data.attributes.template_name', 'Colleague');
    }

    #[Test]
    public function it_refuses_to_write_a_contact_down_with_no_first_name(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts", ['last_name' => 'Beesly']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['first_name']);
    }

    #[Test]
    public function it_keeps_a_contact_written_down_as_partial_off_the_lists(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        Sanctum::actingAs($user);

        $created = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts", [
            'first_name' => 'Jack',
            'is_partial' => true,
        ]);

        $created->assertCreated();
        $created->assertJsonPath('data.attributes.is_partial', true);
        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts")->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_does_not_write_a_contact_down_in_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts", ['first_name' => 'Pam'])->assertNotFound();
        $this->assertDatabaseMissing('contacts', ['first_name' => 'Pam']);
    }

    #[Test]
    public function it_deletes_a_contact_and_keeps_what_they_took_part_in(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}")->assertNoContent();

        $this->assertSoftDeleted($contact);
        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_does_not_delete_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}")->assertNotFound();
        $this->assertNotSoftDeleted($contact);
    }

    private function vaultReadyForContacts(User $user): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, 'Basic contact');

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $template->structure->published_version_id,
        ]);

        return $vault;
    }

    private function publishedContactTemplate(User $user, string $name): Template
    {
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => $name]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $template->structure->update(['published_version_id' => $version->id]);

        return $template;
    }

    #[Test]
    public function it_refuses_a_contact_past_the_ceiling_with_the_cap_named(): void
    {
        Queue::fake();
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts", ['first_name' => 'Pam']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('first_name');
        $response->assertJsonPath('errors.first_name.0', 'The free plan holds 1 contact across every vault, and this account already has it. Full access lifts the ceiling.');
        $this->assertSame(1, $user->account->contactCount());
    }

    #[Test]
    public function it_reaches_a_contact_by_either_spelling_of_its_address(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id, 'account_id' => $user->account_id,
            'first_name' => 'Jim', 'last_name' => 'Halpert',
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/contacts/{$contact->slug}")
            ->assertOk()->assertJsonPath('data.id', $contact->uuid);
        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}")
            ->assertOk()->assertJsonPath('data.id', $contact->uuid);
        $this->json('GET', "/api/v1/vaults/{$vault->slug}/contacts/{$contact->uuid}")
            ->assertOk()->assertJsonPath('data.id', $contact->uuid);
    }

    #[Test]
    public function it_writes_down_that_the_contact_was_read(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Jim']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}")->assertOk();

        Queue::assertPushedOn('low', RecordContactVisit::class);
    }

    #[Test]
    public function it_reaches_a_contact_named_in_another_alphabet_written_either_way(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id, 'account_id' => $user->account_id,
            'first_name' => '太郎', 'last_name' => '田中',
            'name_order' => NameOrderEnum::FamilyFirst,
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/contacts/田中-太郎")
            ->assertOk()->assertJsonPath('data.id', $contact->uuid);
        $this->json('GET', "/api/v1/vaults/{$vault->slug}/contacts/".rawurlencode('田中-太郎'))
            ->assertOk()->assertJsonPath('data.id', $contact->uuid);
    }
}
