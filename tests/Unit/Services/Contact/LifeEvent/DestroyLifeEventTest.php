<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Tests\TestCase;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\LifeEvent\DestroyLifeEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_life_event()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $request = [
            'account_id' => $lifeEvent->account->id,
            'life_event_id' => $lifeEvent->id,
        ];

        $this->assertDatabaseHas('life_events', [
            'id' => $lifeEvent->id,
        ]);

        $lifeEventService = new DestroyLifeEvent;
        $bool = $lifeEventService->execute($request);

        $this->assertDatabaseMissing('life_events', [
            'id' => $lifeEvent->id,
        ]);
    }

    public function test_it_destroys_a_life_event_and_associated_reminder()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $lifeEvent->account_id,
        ]);
        $lifeEvent->reminder_id = $reminder->id;
        $lifeEvent->save();

        $request = [
            'account_id' => $lifeEvent->account->id,
            'life_event_id' => $lifeEvent->id,
        ];

        $lifeEventService = new DestroyLifeEvent;
        $bool = $lifeEventService->execute($request);

        $this->assertDatabaseMissing('reminders', [
            'id' => $reminder->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
        ];

        $this->expectException(MissingParameterException::class);

        $destroyMessage = new DestroyLifeEvent;
        $result = $destroyMessage->execute($request);
    }

    public function test_it_throws_an_exception_if_life_event_doesnt_exist()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $request = [
            'account_id' => 231,
            'life_event_id' => $lifeEvent->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        $destroyMessage = new DestroyLifeEvent;
        $lifeEvent = $destroyMessage->execute($request);
    }
}
