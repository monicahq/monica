<?php

namespace Tests\Unit\Services\Contact\LifeEvent;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\LifeEvent\UpdateLifeEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_life_event()
    {
        $lifeEvent = factory(LifeEvent::class)->create([
            'happened_at' => '2008-01-01',
        ]);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $lifeEvent->account_id,
        ]);

        $request = [
            'life_event_id' => $lifeEvent->id,
            'account_id' => $lifeEvent->account_id,
            'life_event_type_id' => $lifeEventType->id,
            'happened_at' => '2018-01-01',
            'name' => 'This is a name',
            'note' => 'This is a note',
        ];

        $lifeEvent = app(UpdateLifeEvent::class)->execute($request);

        $this->assertDatabaseHas('life_events', [
            'id' => $lifeEvent->id,
            'happened_at' => '2018-01-01 00:00:00',
            'life_event_type_id' => $lifeEventType->id,
            'contact_id' => $lifeEvent->contact_id,
            'account_id' => $lifeEvent->account_id,
            'name' => 'This is a name',
            'note' => 'This is a note',
        ]);

        $this->assertInstanceOf(
            LifeEvent::class,
            $lifeEvent
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'happened_at' => now(),
        ];

        $this->expectException(ValidationException::class);

        app(UpdateLifeEvent::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_life_type_doesnt_exist()
    {
        $account = factory(Account::class)->create();
        $lifeEvent = factory(LifeEvent::class)->create([]);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $lifeEvent->account_id,
        ]);

        $request = [
            'account_id' => $account->id,
            'contact_id' => $lifeEvent->contact_id,
            'life_event_id' => $lifeEvent->id,
            'happened_at' => '2010-02-02',
            'life_event_type_id' => $lifeEventType->id,
            'name' => 'This is a name',
            'note' => 'This is a note',
        ];

        $this->expectException(ModelNotFoundException::class);

        app(UpdateLifeEvent::class)->execute($request);
    }
}
