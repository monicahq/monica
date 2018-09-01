<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;

// use App\Services\Contact\LifeEvent\DestroyMessage;
// use App\Services\Contact\LifeEvent\CreateLifeEvent;
// use App\Services\Contact\LifeEvent\UpdateConversation;
// use App\Services\Contact\LifeEvent\DestroyConversation;
// use App\Services\Contact\LifeEvent\AddMessageToConversation;

class LifeEventsController extends Controller
{
    /**
     * Display the list of life events.
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Contact $contact)
    {
        $lifeEventsCollection = collect([]);
        $lifeEvents = $contact->lifeEvents()->get();

        foreach ($lifeEvents as $lifeEvent) {
            $data = [
                'id' => $lifeEvent->id,
                'life_event_type' => $lifeEvent->lifeEventType->name,
                'name' => $lifeEvent->name,
                'note' => $lifeEvent->note,
                'happened_at' => DateHelper::getShortDate($lifeEvent->happened_at),
            ];
            $lifeEventsCollection->push($data);
        }

        return $lifeEventsCollection;
    }
}
