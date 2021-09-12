<?php

namespace App\Http\Controllers\Account\LifeEvent;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Services\Account\LifeEvent\LifeEventType\CreateLifeEventType;
use App\Services\Account\LifeEvent\LifeEventType\UpdateLifeEventType;
use App\Services\Account\LifeEvent\LifeEventType\DestroyLifeEventType;
use App\Http\Resources\LifeEvent\LifeEventType as LifeEventTypeResource;

class LifeEventTypesController extends Controller
{
    use JsonRespondController;

    /**
     * Store a life event type.
     *
     * @param  Request  $request
     * @return LifeEventTypeResource
     */
    public function store(Request $request)
    {
        $type = app(CreateLifeEventType::class)->execute([
            'account_id' => auth()->user()->account_id,
            'life_event_category_id' => $request->input('life_event_category_id'),
            'name' => $request->input('name'),
        ]);

        return new LifeEventTypeResource($type);
    }

    /**
     * Update a life event type.
     *
     * @param  Request  $request
     * @param  int  $liveEventTypeId
     * @return LifeEventTypeResource
     */
    public function update(Request $request, $liveEventTypeId)
    {
        $data = [
            'account_id' => auth()->user()->account_id,
            'life_event_type_id' => $liveEventTypeId,
            'life_event_category_id' => $request->input('life_event_category_id'),
            'name' => $request->input('name'),
        ];

        $type = app(UpdateLifeEventType::class)->execute($data);

        return new LifeEventTypeResource($type);
    }

    /**
     * Delete the life event type.
     *
     * @param  Request  $request
     * @param  int  $lifeEventTypeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $lifeEventTypeId)
    {
        $data = [
            'account_id' => auth()->user()->account_id,
            'life_event_type_id' => $lifeEventTypeId,
        ];

        try {
            app(DestroyLifeEventType::class)->execute($data);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }

        return $this->respondObjectDeleted($lifeEventTypeId);
    }
}
