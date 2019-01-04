<?php

namespace Tests\Unit\Services\Contact\LifeEvent;

use Tests\TestCase;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\LifeEvent\AddReminderToLifeEvent;

class AddReminderToLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_a_reminder_for_the_life_event()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $request = [
            'account_id' => $lifeEvent->account->id,
            'life_event_id' => $lifeEvent->id,
            'date' => '2010-01-01',
            'frequency_type' => 'year',
            'frequency_number' => 1,
        ];

        $reminderService = new AddReminderToLifeEvent;
        $reminder = $reminderService->execute($request);

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'account_id' => $lifeEvent->account->id,
        ]);

        $this->assertInstanceOf(
            Reminder::class,
            $reminder
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $request = [
            'life_event_id' => $lifeEvent->id,
            'date' => '2010-01-01',
            'frequency_type' => 'year',
            'frequency_number' => 1,
        ];

        $this->expectException(ValidationException::class);

        $reminderService = new AddReminderToLifeEvent;
        $reminder = $reminderService->execute($request);
    }

    public function test_it_throws_an_exception_if_life_event_is_not_linked_to_account()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $request = [
            'account_id' => $lifeEvent->account->id,
            'life_event_id' => 33,
            'date' => '2010-01-01',
            'frequency_type' => 'year',
            'frequency_number' => 1,
        ];

        $this->expectException(ModelNotFoundException::class);

        $reminderService = new AddReminderToLifeEvent;
        $reminder = $reminderService->execute($request);
    }
}
