<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\LifeEvent;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Models\Contact\LifeEventCategory;
use App\Services\Contact\LifeEvent\CreateLifeEvent;
use App\Services\Contact\LifeEvent\DestroyLifeEvent;
use App\Http\Resources\LifeEvent\LifeEventType as LifeEventTypeResource;
use App\Http\Resources\LifeEvent\LifeEventCategory as LifeEventCategoryResource;

class LifeEventsController extends Controller
{
    use JsonRespondController;

    /**
     * Get the list of life event categories.
     *
     * @param  Request $request
     * @return Collection
     */
    public function categories(Request $request)
    {
        $lifeEventCategories = auth()->user()->account->lifeEventCategories;

        return LifeEventCategoryResource::collection($lifeEventCategories);
    }

    /**
     * Get the list of life event types for a given life event category.
     * @param  Request $request
     * @param  int     $LifeEventCategoryId
     * @return Collection
     */
    public function types(Request $request, int $lifeEventCategoryId)
    {
        $lifeEventCategory = LifeEventCategory::findOrFail($lifeEventCategoryId);
        $lifeEventTypes = $lifeEventCategory->lifeEventTypes;

        return LifeEventTypeResource::collection($lifeEventTypes);
    }

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
                'default_life_event_type_key' => $lifeEvent->lifeEventType->default_life_event_type_key,
                'name' => $lifeEvent->name,
                'note' => $lifeEvent->note,
                'happened_at' => DateHelper::getShortDate($lifeEvent->happened_at),
            ];
            $lifeEventsCollection->push($data);
        }

        return $lifeEventsCollection;
    }

    /**
     * Store the life event.
     *
     * @param Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Contact $contact)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'contact_id' => $contact->id,
            'life_event_type_id' => $request->get('life_event_type_id'),
            'happened_at' => $request->get('happened_at'),
            'name' => $request->get('name'),
            'note' => $request->get('note'),
            'has_reminder' => $request->get('has_reminder'),
            'happened_at_month_unknown' => $request->get('happened_at_month_unknown'),
            'happened_at_day_unknown' => $request->get('happened_at_day_unknown'),
        ];

        // create the conversation
        try {
            $lifeEvent = (new CreateLifeEvent)->execute($data);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(trans('app.error_save'));
        }

        return $lifeEvent;
    }

    /**
     * Destroy the life event.
     * @param  Request   $request
     * @param  Contact   $contat
     * @param  LifeEvent $lifeEvent
     * @return bool
     */
    public function destroy(Request $request, Contact $contat, LifeEvent $lifeevent)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'life_event_id' => $lifeevent->id,
        ];

        try {
            (new DestroyLifeEvent)->execute($data);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(trans('app.error_save'));
        }

        return $this->respondObjectDeleted($lifeevent->id);
    }
}
