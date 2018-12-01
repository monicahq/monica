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

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LifeEventTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $this->assertTrue($lifeEvent->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $this->assertTrue($lifeEvent->contact()->exists());
    }

    public function test_it_belongs_to_a_type()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $this->assertTrue($lifeEvent->lifeEventType()->exists());
    }

    public function test_it_has_a_reminder()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $lifeEvent->account_id,
        ]);
        $lifeEvent->reminder_id = $reminder->id;
        $lifeEvent->save();

        $this->assertTrue($lifeEvent->reminder()->exists());
    }

    public function test_it_gets_the_name_attribute()
    {
        $lifeEvent = factory(LifeEvent::class)->create([
            'name' => 'Fake name',
        ]);

        $this->assertEquals(
            'Fake name',
            $lifeEvent->name
        );
    }

    public function test_it_gets_the_note_attribute()
    {
        $lifeEvent = factory(LifeEvent::class)->create([
            'note' => 'Fake note',
        ]);

        $this->assertEquals(
            'Fake note',
            $lifeEvent->note
        );
    }
}
