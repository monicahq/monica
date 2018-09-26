<?php

namespace Tests\Unit\Services\Contact\LifeEvent;

use Carbon\Carbon;
use Tests\TestCase;
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

        $updateConversation = new UpdateLifeEvent;
        $lifeEvent = $updateConversation->execute($request);
    }

    public function test_it_throws_an_exception_if_life_type_doesnt_exist()
    {
        $lifeEvent = factory(LifeEvent::class)->create([]);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $lifeEvent->account->id,
        ]);

        $request = [
            'account_id' => 231,
            'contact_id' => $lifeEvent->contact->id,
            'life_event_id' => $lifeEvent->id,
            'happened_at' => '2010-02-02',
            'life_event_type_id' => $lifeEventType->id,
            'name' => 'This is a name',
            'note' => 'This is a note',
        ];

        $this->expectException(ModelNotFoundException::class);

        $updateConversation = new UpdateLifeEvent;
        $lifeEvent = $updateConversation->execute($request);
    }
}
