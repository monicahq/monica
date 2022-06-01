<?php

namespace App\Settings\ManageLifeEventCategories\Services;

use App\Exceptions\CantBeDeletedException;
use App\Interfaces\ServiceInterface;
use App\Models\Activity;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use App\Services\BaseService;

class DestroyLifeEventType extends BaseService implements ServiceInterface
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
            'life_event_type_id' => 'required|integer|exists:life_event_types,id',
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
     * Destroy a life activity type.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        LifeEventCategory::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_category_id']);

        $type = LifeEventType::where('life_event_category_id', $data['life_event_category_id'])
            ->findOrFail($data['life_event_type_id']);

        if (! $type->can_be_deleted) {
            throw new CantBeDeletedException();
        }

        $type->delete();
    }
}
