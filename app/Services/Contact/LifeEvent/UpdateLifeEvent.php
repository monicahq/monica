<?php

namespace App\Services\Contact\LifeEvent;

use App\Services\BaseService;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;

class UpdateLifeEvent extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'life_event_id' => 'required|integer',
            'life_event_type_id' => 'required|integer',
            'happened_at' => 'required|date',
            'name' => 'nullable|string',
            'note' => 'nullable|string',
        ];
    }

    /**
     * Update a life event.
     *
     * @param array $data
     * @return LifeEvent
     */
    public function execute(array $data) : LifeEvent
    {
        $this->validate($data);

        $lifeEvent = LifeEvent::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_id']);

        LifeEventType::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_type_id']);

        $lifeEvent->update([
            'happened_at' => $data['happened_at'],
            'life_event_type_id' => $data['life_event_type_id'],
            'name' => $data['name'],
            'note' => $data['note'],
        ]);

        return $lifeEvent;
    }
}
