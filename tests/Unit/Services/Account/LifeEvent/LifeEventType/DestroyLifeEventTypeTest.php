<?php

namespace Tests\Unit\Services\Account\LifeEvent\LifeEventType;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\LifeEventType;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\LifeEvent\LifeEventType\DestroyLifeEventType;

class DestroyLifeEventTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_life_event_type()
    {
        $lifeEventType = factory(LifeEventType::class)->create([]);

        $request = [
            'account_id' => $lifeEventType->account_id,
            'life_event_type_id' => $lifeEventType->id,
        ];

        app(DestroyLifeEventType::class)->execute($request);

        $this->assertDatabaseMissing('life_event_types', [
            'id' => $lifeEventType->id,
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_account_is_not_linked_to_life_event_type()
    {
        $account = factory(Account::class)->create([]);
        $lifeEventType = factory(LifeEventType::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'life_event_type_id' => $lifeEventType->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(DestroyLifeEventType::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_ids_do_not_exist()
    {
        $request = [
            'account_id' => 11111111,
            'life_event_type_id' => 11111111,
        ];

        $this->expectException(ValidationException::class);
        app(DestroyLifeEventType::class)->execute($request);
    }
}
