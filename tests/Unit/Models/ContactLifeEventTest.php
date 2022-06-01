<?php

namespace Tests\Unit\Models;

use App\Models\ContactLifeEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $lifeEvent = ContactLifeEvent::factory()->create();

        $this->assertTrue($lifeEvent->contact()->exists());
    }

    /** @test */
    public function it_has_one_type()
    {
        $lifeEvent = ContactLifeEvent::factory()->create();

        $this->assertTrue($lifeEvent->lifeEventType()->exists());
    }
}
