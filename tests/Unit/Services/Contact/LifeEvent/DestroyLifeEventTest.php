<?php

namespace Tests\Unit\Services\Contact\LifeEvent;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\LifeEvent\DestroyLifeEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_life_event()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $request = [
            'account_id' => $lifeEvent->account_id,
            'life_event_id' => $lifeEvent->id,
        ];

        $this->assertDatabaseHas('life_events', [
            'id' => $lifeEvent->id,
        ]);

        app(DestroyLifeEvent::class)->execute($request);

        $this->assertDatabaseMissing('life_events', [
            'id' => $lifeEvent->id,
        ]);
    }

    /** @test */
    public function it_destroys_a_life_event_and_associated_reminder()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $lifeEvent->account_id,
        ]);
        $lifeEvent->reminder_id = $reminder->id;
        $lifeEvent->save();

        $request = [
            'account_id' => $lifeEvent->account_id,
            'life_event_id' => $lifeEvent->id,
        ];

        app(DestroyLifeEvent::class)->execute($request);

        $this->assertDatabaseMissing('reminders', [
            'id' => $reminder->id,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
        ];

        $this->expectException(ValidationException::class);

        app(DestroyLifeEvent::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_life_event_doesnt_exist()
    {
        $account = factory(Account::class)->create();
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'life_event_id' => $lifeEvent->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        app(DestroyLifeEvent::class)->execute($request);
    }
}
