<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Currency;
use App\Models\LifeEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LifeEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $lifeEvent = LifeEvent::factory()->create();

        $this->assertTrue($lifeEvent->vault()->exists());
    }

    /** @test */
    public function it_has_one_type()
    {
        $lifeEvent = LifeEvent::factory()->create();

        $this->assertTrue($lifeEvent->lifeEventType()->exists());
    }

    /** @test */
    public function it_has_one_currency()
    {
        $currency = Currency::factory()->create();
        $lifeEvent = LifeEvent::factory()->create([
            'currency_id' => $currency->id,
        ]);

        $this->assertTrue($lifeEvent->currency()->exists());
    }

    /** @test */
    public function it_has_one_contact_who_pays_for_the_life_event()
    {
        $contact = Contact::factory()->create();
        $lifeEvent = LifeEvent::factory()->create([
            'paid_by_contact_id' => $contact->id,
        ]);

        $this->assertTrue($lifeEvent->paidBy()->exists());
    }

    /** @test */
    public function it_has_many_participants(): void
    {
        $contact = Contact::factory()->create();
        $lifeEvent = LifeEvent::factory()->create();

        $lifeEvent->participants()->sync([$contact->id]);

        $this->assertTrue($lifeEvent->participants()->exists());
    }
}
