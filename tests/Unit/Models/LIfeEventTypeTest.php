<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LIfeEventTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $lifeEventType = factory(LifeEventType::class)->create([]);

        $this->assertTrue($lifeEventType->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_category()
    {
        $lifeEventType = factory(LifeEventType::class)->create([]);

        $this->assertTrue($lifeEventType->lifeEventCategory()->exists());
    }

    /** @test */
    public function it_has_many_life_events()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $lifeEventType = factory(LifeEventType::class)->create([]);
        $lifeEvents = factory(LifeEvent::class, 2)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'life_event_type_id' => $lifeEventType->id,
        ]);

        $this->assertTrue($lifeEventType->lifeEvents()->exists());
    }

    /** @test */
    public function it_gets_the_name_attribute()
    {
        $lifeEventType = factory(LifeEventType::class)->create([
            'name' => 'Fake name',
        ]);

        $this->assertEquals(
            'Fake name',
            $lifeEventType->name
        );
    }
}
