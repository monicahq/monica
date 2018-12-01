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
use App\Models\Account\Account;
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

        $destroyMessage = (new DestroyLifeEvent)->execute($request);
    }

    public function test_it_throws_an_exception_if_life_event_doesnt_exist()
    {
        $account = factory(Account::class)->create();
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'life_event_id' => $lifeEvent->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        $destroyMessage = (new DestroyLifeEvent)->execute($request);
    }
}
