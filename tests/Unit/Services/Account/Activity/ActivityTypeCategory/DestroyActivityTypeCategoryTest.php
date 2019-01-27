<?php

namespace Tests\Unit\Services\Account\Activity\ActivityTypeCategory;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\ActivityTypeCategory;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\Activity\ActivityTypeCategory\DestroyActivityTypeCategory;

class DestroyActivityTypeCategoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_activity_type_category()
    {
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([]);

        $request = [
            'account_id' => $activityTypeCategory->account_id,
            'activity_type_category_id' => $activityTypeCategory->id,
        ];

        app(DestroyActivityTypeCategory::class)->execute($request);

        $this->assertDatabaseMissing('activity_type_categories', [
            'id' => $activityTypeCategory->id,
        ]);
    }

    public function test_it_throws_an_exception_if_account_is_not_linked_to_activity_type_category()
    {
        $account = factory(Account::class)->create([]);
        $activityTypeCategory = factory(ActivityTypeCategory::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'activity_type_category_id' => $activityTypeCategory->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(DestroyActivityTypeCategory::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_ids_do_not_exist()
    {
        $request = [
            'account_id' => 11111111,
            'activity_type_category_id' => 11111111,
        ];

        $this->expectException(ValidationException::class);
        app(DestroyActivityTypeCategory::class)->execute($request);
    }
}
