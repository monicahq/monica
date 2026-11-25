<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            '*' => [
                'type',
                'id',
                'attributes' => ['label', 'is_active', 'contacts_count'],
            ],
        ],
    ];

    #[Test]
    public function it_lists_the_categories_somebody_in_the_vault_holds_with_how_many_hold_each(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $user->account_id]);
        $sales = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Sales']);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Nobody holds this']);
        $field = Field::factory()->create(['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);

        foreach (['Jim', 'Dwight'] as $firstName) {
            $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => $firstName]);
            FieldValue::factory()->create(['valuable_id' => $contact->id, 'field_id' => $field->id, 'value_string' => null, 'value_list_item_ids' => [$sales->id]]);
        }

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contact-categories");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $sales->id);
        $response->assertJsonPath('data.0.attributes.contacts_count', 2);
    }

    #[Test]
    public function it_refuses_a_grouping_it_does_not_offer(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contact-categories?filter=archived")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('filter');
    }

    #[Test]
    public function it_does_not_find_a_vault_of_another_account(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', '/api/v1/vaults/'.Vault::factory()->create()->uuid.'/contact-categories')
            ->assertNotFound();
    }

    #[Test]
    public function it_lists_a_category_held_in_a_sensitive_field_only_to_a_key_reaching_sensitive_fields(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $user->account_id]);
        $therapy = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'In therapy']);
        $field = Field::factory()->create(['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id, 'is_sensitive' => true]);
        $michael = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael']);
        FieldValue::factory()->create(['valuable_id' => $michael->id, 'field_id' => $field->id, 'value_string' => null, 'value_list_item_ids' => [$therapy->id]]);

        $this->withToken($user->createToken('Dwight’s script')->plainTextToken)
            ->json('GET', "/api/v1/vaults/{$vault->uuid}/contact-categories")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->app['auth']->forgetGuards();

        $this->withToken($user->createToken('Dwight’s trusted script', ['sensitive-fields'])->plainTextToken)
            ->json('GET', "/api/v1/vaults/{$vault->uuid}/contact-categories")
            ->assertOk()
            ->assertJsonPath('data.0.attributes.label', 'In therapy');
    }

    #[Test]
    public function it_gives_the_people_listed_their_categories(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        [$sales, $jim] = $this->contactWithCategoryField($user, $vault, 'Jim');
        $toby = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Toby']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/categories", ['contacts' => [
            ['contact' => $jim->uuid, 'categories' => [$sales->id]],
            ['contact' => $toby->uuid, 'categories' => []],
        ]])
            ->assertOk()
            ->assertJsonPath('data.type', 'contact_category_change')
            ->assertJsonPath('data.attributes.changed', [$jim->uuid])
            ->assertJsonPath('data.attributes.unchanged.0.contact', $toby->uuid)
            ->assertJsonPath('data.attributes.unchanged.0.reason', 'Their template has no category field.');

        $this->assertSame([$sales->id], $jim->heldCategories(reachesSensitiveFields: true)->pluck('id')->all());
    }

    #[Test]
    public function it_refuses_more_than_five_hundred_people(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/categories", [
            'contacts' => array_map(fn (int $index): array => ['contact' => "someone-{$index}", 'categories' => []], range(1, 501)),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contacts');
    }

    #[Test]
    public function it_refuses_a_person_of_another_vault(): void
    {
        $user = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $user->account_id]);
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $karen = Contact::factory()->create(['vault_id' => $stamford->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$scranton->uuid}/contacts/categories", ['contacts' => [
            ['contact' => $karen->uuid, 'categories' => []],
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contacts.0.contact');
    }

    #[Test]
    public function it_refuses_a_category_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        [, $jim] = $this->contactWithCategoryField($user, $vault, 'Jim');
        $elsewhere = ValueListItem::factory()->create();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/categories", ['contacts' => [
            ['contact' => $jim->uuid, 'categories' => [$elsewhere->id]],
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contacts.0.categories');
    }

    /** @return array{0: ValueListItem, 1: Contact} */
    private function contactWithCategoryField(User $user, Vault $vault, string $firstName): array
    {
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $user->account_id]);
        $sales = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Sales']);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create(['structure_version_id' => $version->id, 'section_version_id' => $section->id]);
        Field::factory()->create(['section_version_id' => $section->id, 'type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $version->id,
            'first_name' => $firstName,
        ]);

        return [$sales, $contact];
    }
}
