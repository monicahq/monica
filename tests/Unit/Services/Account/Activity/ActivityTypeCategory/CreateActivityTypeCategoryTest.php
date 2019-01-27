<?php

namespace Tests\Unit\Services\Account\Activity\ActivityTypeCategory;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\ActivityTypeCategory;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Account\Activity\ActivityTypeCategory\CreateActivityTypeCategory;

class CreateActivityTypeCategoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_an_activity_type_category()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'name' => 'central perk',
            'translation_key' => 'central_perk',
        ];

        $activityTypeCategory = app(CreateActivityTypeCategory::class)->execute($request);

        $this->assertDatabaseHas('activity_type_categories', [
            'id' => $activityTypeCategory->id,
            'account_id' => $account->id,
            'name' => 'central perk',
            'translation_key' => 'central_perk',
        ]);

        $this->assertInstanceOf(
            ActivityTypeCategory::class,
            $activityTypeCategory
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'name' => '199 Lafayette Street',
        ];

        $this->expectException(ValidationException::class);
        app(CreateActivityTypeCategory::class)->execute($request);
    }
}
