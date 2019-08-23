<?php

namespace Tests\Unit\Services\Account\Activity\ActivityType;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\ActivityType;
use App\Models\Account\ActivityTypeCategory;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\Activity\ActivityType\UpdateActivityType;

class UpdateActivityTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_activity_type()
    {
        $activityType = factory(ActivityType::class)->create([]);
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $activityType->account_id,
        ]);

        $request = [
            'account_id' => $activityType->account_id,
            'activity_type_id' => $activityType->id,
            'activity_type_category_id' => $activityTypeCategory->id,
            'name' => 'Chandler House',
            'translation_key' => 'https://centralperk.com',
        ];

        $activityType = app(UpdateActivityType::class)->execute($request);

        $this->assertDatabaseHas('activity_types', [
            'id' => $activityType->id,
            'account_id' => $activityType->account_id,
            'activity_type_category_id' => $activityTypeCategory->id,
            'name' => 'Chandler House',
            'translation_key' => 'https://centralperk.com',
        ]);

        $this->assertInstanceOf(
            ActivityType::class,
            $activityType
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $activityType = factory(ActivityType::class)->create([]);
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $activityType->account_id,
        ]);

        $request = [
            'account_id' => $activityType->account_id,
            'activity_type_category_id' => $activityTypeCategory->id,
            'name' => 'Chandler House',
            'translation_key' => 'https://centralperk.com',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateActivityType::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_activity_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $activityType = factory(ActivityType::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'activity_type_id' => $activityType->id,
            'activity_type_category_id' => $activityType->activity_type_category_id,
            'name' => 'Chandler House',
            'translation_key' => 'https://centralperk.com',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateActivityType::class)->execute($request);
    }
}
