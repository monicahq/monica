<?php

namespace Tests\Unit\Services\Account\Activity\ActivityType;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\ActivityType;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\Activity\ActivityType\DestroyActivityType;

class DestroyActivityTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_activity_type()
    {
        $activityType = factory(ActivityType::class)->create([]);

        $request = [
            'account_id' => $activityType->account_id,
            'activity_type_id' => $activityType->id,
        ];

        app(DestroyActivityType::class)->execute($request);

        $this->assertDatabaseMissing('activity_types', [
            'id' => $activityType->id,
        ]);
    }

    public function test_it_throws_an_exception_if_account_is_not_linked_to_activity_type()
    {
        $account = factory(Account::class)->create([]);
        $activityType = factory(ActivityType::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'activity_type_id' => $activityType->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(DestroyActivityType::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_ids_do_not_exist()
    {
        $request = [
            'account_id' => 11111111,
            'activity_type_id' => 11111111,
        ];

        $this->expectException(ValidationException::class);
        app(DestroyActivityType::class)->execute($request);
    }
}
