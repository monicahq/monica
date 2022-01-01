<?php

namespace App\Services\Account\LifeEvent\LifeEventType;

use App\Services\BaseService;
use App\Models\Contact\LifeEventType;

class DestroyLifeEventType extends BaseService
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
            'life_event_type_id' => 'required|integer|exists:life_event_types,id',
        ];
    }

    /**
     * Destroy a life event type.
     *
     * @param  array  $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        $lifeEventType = LifeEventType::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_type_id']);

        $lifeEventType->delete();

        return true;
    }
}
