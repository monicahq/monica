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
     * @param  Request  $request
     * @return \Illuminate\Http\Resources\Json\ResourceCollection
     */
    public function categories(Request $request)
    {
        $lifeEventCategories = auth()->user()->account->lifeEventCategories;

        return LifeEventCategoryResource::collection($lifeEventCategories);
    }

    /**
     * Get the list of life event types for a given life event category.
     *
     * @param  Request  $request
     * @param  int  $lifeEventCategoryId
     * @return \Illuminate\Http\Resources\Json\ResourceCollection
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
     * @param  Request  $request
     * @param  Contact  $contact
     * @return Collection
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
                'life_event_type_name' => $lifeEvent->lifeEventType->name,
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
     * @param  Request  $request
     * @param  Contact  $contact
     * @return LifeEvent|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Contact $contact)
    {
        $data = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'life_event_type_id' => $request->input('life_event_type_id'),
            'happened_at' => $request->input('happened_at'),
            'name' => $request->input('name'),
            'note' => $request->input('note'),
            'has_reminder' => $request->input('has_reminder'),
            'happened_at_month_unknown' => $request->input('happened_at_month_unknown'),
            'happened_at_day_unknown' => $request->input('happened_at_day_unknown'),
        ];

        // create the conversation
        try {
            $lifeEvent = app(CreateLifeEvent::class)->execute($data);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(trans('app.error_save'));
        }

        return $lifeEvent;
    }

    /**
     * Destroy the life event.
     *
     * @param  Request  $request
     * @param  LifeEvent  $lifeEvent
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, LifeEvent $lifeEvent)
    {
        $data = [
            'account_id' => auth()->user()->account_id,
            'life_event_id' => $lifeEvent->id,
        ];

        try {
            app(DestroyLifeEvent::class)->execute($data);
        } catch (\Exception $e) {
            // We have to redirect with HTTP status 303 or the browser will issue a
            // DELETE request to the new location. This may result in deleting other
            // resources as well. Refer to Github issue #2415
            return back(303)
                ->withInput()
                ->withErrors(trans('app.error_save'));
        }

        return $this->respondObjectDeleted($lifeEvent->id);
    }
}
