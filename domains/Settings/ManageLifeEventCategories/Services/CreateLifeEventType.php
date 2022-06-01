<?php

namespace App\Settings\ManageLifeEventCategories\Services;

use App\Interfaces\ServiceInterface;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use App\Services\BaseService;

class CreateLifeEventType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'life_event_category_id' => 'required|integer|exists:life_event_categories,id',
            'label' => 'required|string|max:255',
            'can_be_deleted' => 'required|boolean',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
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
        $this->validateRules($data);

        $category = LifeEventCategory::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_category_id']);

        // determine the new position of the template page
        $newPosition = LifeEventType::where('life_event_category_id', $data['life_event_category_id'])
            ->max('position');
        $newPosition++;

        $type = LifeEventType::create([
            'life_event_category_id' => $category->id,
            'label' => $data['label'],
            'can_be_deleted' => $data['can_be_deleted'],
            'position' => $newPosition,
        ]);

        return $type;
    }
}
