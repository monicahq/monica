<?php

namespace App\Services\Contact\LifeEvent;

use App\Services\BaseService;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;

class DestroyLifeEvent extends BaseService
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
        ];
    }

    /**
     * Destroy a life event.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

        $lifeEvent = LifeEvent::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_id']);

        $this->deleteAssociatedReminder($lifeEvent);

        $lifeEvent->delete();

        return true;
    }

    /**
     * Delete the associated reminder, if it's set.
     */
    private function deleteAssociatedReminder($lifeEvent)
    {
        if ($lifeEvent->reminder_id) {
            Reminder::where('id', $lifeEvent->reminder_id)->delete();
        }
    }
}
