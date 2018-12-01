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

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\LifeEvent\UpdateLifeEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_life_event()
    {
        $lifeEvent = factory(LifeEvent::class)->create([
            'happened_at' => '2008-01-01',
        ]);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $lifeEvent->account->id,
        ]);

        $request = [
            'life_event_id' => $lifeEvent->id,
            'account_id' => $lifeEvent->account->id,
            'life_event_type_id' => $lifeEventType->id,
            'happened_at' => '2018-01-01',
            'name' => 'This is a name',
            'note' => 'This is a note',
        ];

        $lifeEventService = new UpdateLifeEvent;
        $lifeEvent = $lifeEventService->execute($request);

        $this->assertDatabaseHas('life_events', [
            'id' => $lifeEvent->id,
            'happened_at' => '2018-01-01 00:00:00',
            'life_event_type_id' => $lifeEventType->id,
            'contact_id' => $lifeEvent->contact->id,
            'account_id' => $lifeEvent->account->id,
            'name' => 'This is a name',
            'note' => 'This is a note',
        ]);

        $this->assertInstanceOf(
            LifeEvent::class,
            $lifeEvent
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'happened_at' => Carbon::now(),
        ];

        $this->expectException(MissingParameterException::class);

        $updateConversation = (new UpdateLifeEvent)->execute($request);
    }

    public function test_it_throws_an_exception_if_life_type_doesnt_exist()
    {
        $account = factory(Account::class)->create();
        $lifeEvent = factory(LifeEvent::class)->create([]);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $lifeEvent->account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'contact_id' => $lifeEvent->contact->id,
            'life_event_id' => $lifeEvent->id,
            'happened_at' => '2010-02-02',
            'life_event_type_id' => $lifeEventType->id,
            'name' => 'This is a name',
            'note' => 'This is a note',
        ];

        $this->expectException(ModelNotFoundException::class);

        $updateConversation = (new UpdateLifeEvent)->execute($request);
    }
}
