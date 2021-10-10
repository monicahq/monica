<?php

namespace App\Services\Account\LifeEvent\LifeEventType;

use App\Services\BaseService;
use App\Models\Contact\LifeEventType;
use App\Models\Contact\LifeEventCategory;

class CreateLifeEventType extends BaseService
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
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Create a life event type.
     *
     * @param  array  $data
     * @return LifeEventType
     */
    public function execute(array $data): LifeEventType
    {
        $this->validate($data);

        LifeEventCategory::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_category_id']);

        $lifeEventType = LifeEventType::create([
            'account_id' => $data['account_id'],
            'life_event_category_id' => $data['life_event_category_id'],
            'name' => $data['name'],
            'default_life_event_type_key' => null,
            'core_monica_data' => false,
            'specific_information_structure' => null,
        ]);

        return LifeEventType::find($lifeEventType->id);
    }
}
