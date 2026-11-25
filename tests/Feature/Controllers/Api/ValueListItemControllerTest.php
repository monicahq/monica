<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\ValueListEnum;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListItemControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => ['label', 'country_code', 'is_active', 'position'],
    ];

    #[Test]
    public function it_adds_a_value_to_a_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/value-lists/{$suppliers->id}/items", ['label' => 'Staples'])
            ->assertCreated()
            ->assertJsonStructure(['data' => $this->jsonStructure])
            ->assertJsonPath('data.attributes.label', 'Staples');

        $this->assertDatabaseHas('value_list_items', ['value_list_id' => $suppliers->id, 'label' => 'Staples']);
    }

    #[Test]
    public function it_refuses_a_country_code_on_a_list_that_holds_no_countries(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/value-lists/{$suppliers->id}/items", ['label' => 'Staples', 'country_code' => 'US'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('country_code');
    }

    #[Test]
    public function it_adds_a_country_by_its_code(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $countries = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/value-lists/{$countries->id}/items", ['country_code' => 'jp'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.country_code', 'JP');
    }

    #[Test]
    public function it_refuses_a_value_on_a_list_that_takes_none_of_its_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $closed = ValueList::factory()->create(['account_id' => $user->account_id, 'allows_user_values' => false]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/value-lists/{$closed->id}/items", ['label' => 'Staples'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valueList' => 'This value list does not take choices of its own.']);
    }

    #[Test]
    public function it_renames_a_value(): void
    {
        Queue::fake();

        [$user, $suppliers, $staples] = $this->staples();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/value-lists/{$suppliers->id}/items/{$staples->id}", ['label' => 'Office Depot'])
            ->assertOk()
            ->assertJsonPath('data.attributes.label', 'Office Depot');

        $this->assertSame('Office Depot', $staples->refresh()->label);
    }

    #[Test]
    public function it_does_not_find_a_value_through_another_list(): void
    {
        Queue::fake();

        [$user, , $staples] = $this->staples();
        $other = ValueList::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/value-lists/{$other->id}/items/{$staples->id}", ['label' => 'Office Depot'])->assertNotFound();
    }

    #[Test]
    public function it_deletes_a_value_nothing_uses(): void
    {
        Queue::fake();

        [$user, $suppliers, $staples] = $this->staples();

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/value-lists/{$suppliers->id}/items/{$staples->id}")->assertNoContent();

        $this->assertSoftDeleted($staples);
    }

    #[Test]
    public function it_refuses_to_delete_a_value_in_use(): void
    {
        Queue::fake();

        [$user, $suppliers, $staples] = $this->staples();
        $staples->update(['usage_count' => 3]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/value-lists/{$suppliers->id}/items/{$staples->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valueListItem' => 'The value is in use and stays.']);
    }

    /** @return array{0: User, 1: ValueList, 2: ValueListItem} */
    private function staples(): array
    {
        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);
        $staples = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $suppliers->id, 'label' => 'Staples', 'position' => 1]);

        return [$user, $suppliers, $staples];
    }

    #[Test]
    public function it_does_not_find_a_value_of_another_account(): void
    {
        Queue::fake();

        $list = ValueList::factory()->create();
        $item = ValueListItem::factory()->create(['account_id' => $list->account_id, 'value_list_id' => $list->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->json('PUT', "/api/v1/value-lists/{$list->id}/items/{$item->id}", ['label' => 'Staples'])->assertNotFound();
    }
}
