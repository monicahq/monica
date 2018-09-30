<?php

namespace Tests\Unit\Services\Contact\LifeEvent;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\LifeEvent\CreateLifeEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_life_event()
    {
        $contact = factory(Contact::class)->create([]);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'life_event_type_id' => $lifeEventType->id,
            'happened_at' => Carbon::now(),
            'name' => 'This is a name',
            'note' => 'This is a note',
            'has_reminder' => false,
            'happened_at_day_unknown' => false,
            'happened_at_month_unknown' => false,
        ];

        $lifeEventService = new CreateLifeEvent;
        $lifeEvent = $lifeEventService->execute($request);

        $this->assertDatabaseHas('life_events', [
            'id' => $lifeEvent->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'life_event_type_id' => $lifeEventType->id,
            'name' => 'This is a name',
            'note' => 'This is a note',
            'reminder_id' => null,
        ]);

        $this->assertInstanceOf(
            LifeEvent::class,
            $lifeEvent
        );
    }

    public function test_it_stores_a_life_event_and_set_a_reminder()
    {
        $contact = factory(Contact::class)->create([]);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'life_event_type_id' => $lifeEventType->id,
            'happened_at' => Carbon::now(),
            'name' => 'This is a name',
            'note' => 'This is a note',
            'has_reminder' => true,
            'happened_at_day_unknown' => false,
            'happened_at_month_unknown' => false,
        ];

        $lifeEventService = new CreateLifeEvent;
        $lifeEvent = $lifeEventService->execute($request);

        $this->assertDatabaseHas('reminders', [
            'id' => $lifeEvent->reminder->id,
        ]);

        $this->assertDatabaseHas('life_events', [
            'reminder_id' => $lifeEvent->reminder->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'happened_at' => Carbon::now(),
        ];

        $this->expectException(MissingParameterException::class);

        $createLifeEvent = new CreateLifeEvent;
        $lifeEvent = $createLifeEvent->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);

        $request = [
            'contact_id' => $lifeEvent->contact->id,
            'account_id' => 2,
            'life_event_type_id' => $lifeEvent->lifeEventType->id,
            'name' => 'This is a name',
            'note' => 'This is a note',
            'has_reminder' => false,
            'happened_at_day_unknown' => false,
            'happened_at_month_unknown' => false,
            'happened_at' => Carbon::now(),
        ];

        $this->expectException(ModelNotFoundException::class);

        $createLifeEvent = new CreateLifeEvent;
        $lifeEvent = $createLifeEvent->execute($request);
    }

    public function test_it_throws_an_exception_if_life_event_type_is_not_linked_to_account()
    {
        $contact = factory(Contact::class)->create([]);
        $lifeEventType = factory(LifeEventType::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'life_event_type_id' => $lifeEventType->id,
            'name' => 'This is a name',
            'note' => 'This is a note',
            'has_reminder' => false,
            'happened_at_day_unknown' => false,
            'happened_at_month_unknown' => false,
            'happened_at' => Carbon::now(),
        ];

        $this->expectException(ModelNotFoundException::class);

        $createLifeEvent = new CreateLifeEvent;
        $lifeEvent = $createLifeEvent->execute($request);
    }
}
