<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListContacts;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListContactsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_the_contacts_of_the_vault_it_was_given(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael']);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $vault->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Michael');
    }

    #[Test]
    public function it_leaves_out_the_contacts_of_another_vault(): void
    {
        $user = User::factory()->create();
        $asked = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $elsewhere->id, 'account_id' => $user->account_id, 'first_name' => 'Creed']);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $asked->uuid])
            ->assertOk()
            ->assertDontSee('Creed');
    }

    #[Test]
    public function it_narrows_the_list_to_a_search(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $vault->uuid, 'query' => 'Beesly'])
            ->assertOk()
            ->assertSee('Beesly')
            ->assertDontSee('Scott');
    }

    #[Test]
    public function it_narrows_the_list_to_the_people_holding_any_of_the_categories_asked_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $user->account_id]);
        $sales = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $field = Field::factory()->create(['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'last_name' => 'Halpert']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'last_name' => 'Flenderson']);
        FieldValue::factory()->create(['valuable_id' => $jim->id, 'field_id' => $field->id, 'value_string' => null, 'value_list_item_ids' => [$sales->id]]);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $vault->uuid, 'categories' => [$sales->id]])
            ->assertOk()
            ->assertSee('Halpert')
            ->assertDontSee('Flenderson');
    }

    #[Test]
    public function it_answers_the_page_that_was_asked_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Angela', 'created_at' => '2026-01-01']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Dwight', 'created_at' => '2026-02-01']);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Kevin', 'created_at' => '2026-03-01']);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $vault->uuid, 'sort' => 'added', 'page' => 2, 'per_page' => 2])
            ->assertOk()
            ->assertSee('Kevin')
            ->assertDontSee('Angela')
            ->assertSee('"page":2')
            ->assertSee('"total":3');
    }

    #[Test]
    public function it_brings_an_absurd_page_size_back_into_range(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $vault->uuid, 'per_page' => 1000])
            ->assertOk()
            ->assertSee('"per_page":'.config('monica.api_maximum_items_per_page'));
    }

    #[Test]
    public function it_refuses_a_page_size_that_is_not_a_number(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $vault->uuid, 'per_page' => 'many'])
            ->assertHasErrors();
    }

    #[Test]
    public function it_answers_an_empty_vault_without_erroring(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(ListContacts::class, ['vault' => $vault->uuid])
            ->assertOk()
            ->assertHasNoErrors();
    }

    #[Test]
    public function it_refuses_without_a_vault(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(ListContacts::class, [])->assertHasErrors();
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.contacts', app(ListContacts::class)->mirrors());
    }
}
