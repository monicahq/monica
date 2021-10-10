<?php

namespace App\Services\Account\LifeEvent\LifeEventType;

use App\Services\BaseService;
use App\Models\Contact\LifeEventType;
use App\Models\Contact\LifeEventCategory;

class UpdateLifeEventType extends BaseService
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
            'life_event_category_id' => 'required|integer|exists:life_event_categories,id',
            'life_event_type_id' => 'required|integer|exists:life_event_types,id',
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Update a life event type.
     *
     * @param  array  $data
     * @return LifeEventType
     */
    public function execute(array $data): LifeEventType
    {
        $this->validate($data);

        LifeEventCategory::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_category_id']);

        /** @var LifeEventType */
        $lifeEventType = LifeEventType::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_type_id']);

        $lifeEventType->update([
            'life_event_category_id' => $data['life_event_category_id'],
            'name' => $data['name'],
        ]);

        return $lifeEventType;
    }
}
