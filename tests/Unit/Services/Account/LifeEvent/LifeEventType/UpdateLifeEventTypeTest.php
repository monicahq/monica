<?php

namespace Tests\Unit\Services\Account\LifeEvent\LifeEventType;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\LifeEventType;
use App\Models\Contact\LifeEventCategory;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\LifeEvent\LifeEventType\UpdateLifeEventType;

class UpdateLifeEventTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_life_event_type()
    {
        $lifeEventType = factory(LifeEventType::class)->create([]);
        $lifeEventCategory = factory(LifeEventCategory::class)->create([
            'account_id' => $lifeEventType->account_id,
        ]);

        $request = [
            'account_id' => $lifeEventType->account_id,
            'life_event_type_id' => $lifeEventType->id,
            'life_event_category_id' => $lifeEventCategory->id,
            'name' => 'Had a major health problem',
        ];

        $lifeEventType = app(UpdateLifeEventType::class)->execute($request);

        $this->assertDatabaseHas('life_event_types', [
            'id' => $lifeEventType->id,
            'account_id' => $lifeEventType->account_id,
            'life_event_category_id' => $lifeEventCategory->id,
            'name' => 'Had a major health problem',
        ]);

        $this->assertInstanceOf(
            LifeEventType::class,
            $lifeEventType
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $lifeEventType = factory(LifeEventType::class)->create([]);
        $lifeEventCategory = factory(LifeEventCategory::class)->create([
            'account_id' => $lifeEventType->account_id,
        ]);

        $request = [
            'account_id' => $lifeEventType->account_id,
            'life_event_category_id' => $lifeEventCategory->id,
            'name' => 'Had a major health problem',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateLifeEventType::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_life_event_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $lifeEventType = factory(LifeEventType::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'life_event_type_id' => $lifeEventType->id,
            'life_event_category_id' => $lifeEventType->life_event_category_id,
            'name' => 'Had a major health problem',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateLifeEventType::class)->execute($request);
    }
}
