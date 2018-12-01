<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



namespace Tests\Unit\Services\Contact\LifeEvent;

use Tests\TestCase;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use App\Exceptions\MissingParameterException;
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

        $this->expectException(MissingParameterException::class);

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
