<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\CountryEnum;
use App\Enums\ValueListEnum;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CountryControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = ['code', 'name', 'favourite_id'];

    #[Test]
    public function it_lists_the_favourites_first_then_every_country_in_the_language_asked_for(): void
    {
        $user = User::factory()->create();
        $countries = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
        $canada = ValueListItem::factory()->supplied(CountryEnum::Canada)->create(['account_id' => $user->account_id, 'value_list_id' => $countries->id, 'position' => 2]);
        $usa = ValueListItem::factory()->supplied(CountryEnum::UnitedStates)->create(['account_id' => $user->account_id, 'value_list_id' => $countries->id, 'position' => 1]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $countries->id, 'label' => 'Schrute Farms', 'country_code' => null, 'position' => 3]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/countries', [], ['Accept-Language' => 'fr']);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure]]);
        $response->assertJsonCount(258, 'data');
        $response->assertJsonPath('data.0', ['code' => 'US', 'name' => 'États-Unis', 'favourite_id' => $usa->id]);
        $response->assertJsonPath('data.1.favourite_id', $canada->id);
        $response->assertJsonFragment(['code' => 'DE', 'name' => 'Allemagne', 'favourite_id' => null]);
        $response->assertJsonFragment(['code' => 'CA', 'name' => 'Canada', 'favourite_id' => $canada->id]);
    }

    #[Test]
    public function it_leaves_out_the_favourites_of_another_account(): void
    {
        $user = User::factory()->create();
        $theirs = ValueListItem::factory()->supplied(CountryEnum::France)->create([
            'value_list_id' => ValueList::factory()->create(['key' => ValueListEnum::Countries->value])->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/countries');

        $response->assertJsonCount(256, 'data');
        $response->assertJsonMissing(['favourite_id' => $theirs->id]);
    }
}
