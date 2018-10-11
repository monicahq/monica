<?php

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
