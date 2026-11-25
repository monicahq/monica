<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\CountryEnum;
use App\Enums\ValueListEnum;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListItemActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_offering_a_value_and_offers_it_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);
        $staples = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $suppliers->id, 'position' => 1]);
        $url = "/api/v1/value-lists/{$suppliers->id}/items/{$staples->id}/activation";

        Sanctum::actingAs($user);

        $this->json('PUT', $url, ['is_active' => false])->assertOk()->assertJsonPath('data.attributes.is_active', false);
        $this->json('PUT', $url, ['is_active' => true])->assertOk()->assertJsonPath('data.attributes.is_active', true);
    }

    #[Test]
    public function it_refuses_an_activation_that_is_not_a_boolean(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);
        $staples = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $suppliers->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/value-lists/{$suppliers->id}/items/{$staples->id}/activation", ['is_active' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    #[Test]
    public function it_refuses_a_country_the_list_already_offers_on_the_field_it_was_sent(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $countries = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
        $stopped = ValueListItem::factory()->supplied(CountryEnum::France)->disabled()->create(['account_id' => $user->account_id, 'value_list_id' => $countries->id]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $countries->id, 'label' => null, 'country_code' => 'FR']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/value-lists/{$countries->id}/items/{$stopped->id}/activation", ['is_active' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active' => 'This country is already on the list.'])
            ->assertJsonMissingValidationErrors('active');
    }

    #[Test]
    public function it_does_not_find_a_value_of_another_account(): void
    {
        Queue::fake();

        $list = ValueList::factory()->create();
        $item = ValueListItem::factory()->create(['account_id' => $list->account_id, 'value_list_id' => $list->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->json('PUT', "/api/v1/value-lists/{$list->id}/items/{$item->id}/activation", ['is_active' => false])->assertNotFound();
    }
}
