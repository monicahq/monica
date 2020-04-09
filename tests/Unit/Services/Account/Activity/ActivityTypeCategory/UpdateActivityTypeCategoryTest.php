<?php

namespace Tests\Unit\Services\Account\Activity\ActivityTypeCategory;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\ActivityTypeCategory;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\Activity\ActivityTypeCategory\UpdateActivityTypeCategory;

class UpdateActivityTypeCategoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_activity_type_category()
    {
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([]);

        $request = [
            'account_id' => $activityTypeCategory->account_id,
            'activity_type_category_id' => $activityTypeCategory->id,
            'name' => 'Chandler House',
            'translation_key' => 'https://centralperk.com',
        ];

        $activityTypeCategory = app(UpdateActivityTypeCategory::class)->execute($request);

        $this->assertDatabaseHas('activity_type_categories', [
            'id' => $activityTypeCategory->id,
            'account_id' => $activityTypeCategory->account_id,
            'name' => 'Chandler House',
            'translation_key' => 'https://centralperk.com',
        ]);

        $this->assertInstanceOf(
            ActivityTypeCategory::class,
            $activityTypeCategory
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([]);

        $request = [
            'name' => '199 Lafayette Street',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateActivityTypeCategory::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_activity_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'activity_type_category_id' => $activityTypeCategory->id,
            'name' => '199 Lafayette Street',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateActivityTypeCategory::class)->execute($request);
    }
}
