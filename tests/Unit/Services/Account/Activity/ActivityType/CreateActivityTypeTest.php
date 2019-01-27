<?php

namespace Tests\Unit\Services\Account\Activity\ActivityType;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\ActivityType;
use App\Models\Account\ActivityTypeCategory;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\Activity\ActivityType\CreateActivityType;

class CreateActivityTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_an_activity_type()
    {
        $account = factory(Account::class)->create([]);
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'activity_type_category_id' => $activityTypeCategory->id,
            'name' => 'central perk',
            'translation_key' => 'central_perk',
        ];

        $activityType = app(CreateActivityType::class)->execute($request);

        $this->assertDatabaseHas('activity_types', [
            'id' => $activityType->id,
            'account_id' => $account->id,
            'activity_type_category_id' => $activityTypeCategory->id,
            'name' => 'central perk',
            'translation_key' => 'central_perk',
        ]);

        $this->assertInstanceOf(
            ActivityType::class,
            $activityType
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'name' => '199 Lafayette Street',
        ];

        $this->expectException(ValidationException::class);
        app(CreateActivityType::class)->execute($request);
    }

    public function test_it_fails_if_activity_type_category_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'activity_type_category_id' => $activityTypeCategory->id,
            'name' => 'central perk',
            'translation_key' => 'central_perk',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(CreateActivityType::class)->execute($request);
    }
}
