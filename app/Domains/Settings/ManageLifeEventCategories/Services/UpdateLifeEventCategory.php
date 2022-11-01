<?php

namespace App\Domains\Settings\ManageLifeEventCategories\Services;

use App\Interfaces\ServiceInterface;
use App\Models\LifeEventCategory;
use App\Models\User;
use App\Services\BaseService;

class UpdateLifeEventCategory extends BaseService implements ServiceInterface
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
            'type' => 'nullable|string|max:255',
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
     * Update a life event category.
     *
     * @param  array  $data
     * @return LifeEventCategory
     */
    public function execute(array $data): LifeEventCategory
    {
        $this->validateRules($data);

        $category = LifeEventCategory::where('account_id', $data['account_id'])
            ->findOrFail($data['life_event_category_id']);

        $category->label = $data['label'];
        $category->can_be_deleted = $data['can_be_deleted'];
        $category->type = $this->valueOrNull($data, 'type');
        $category->save();

        return $category;
    }
}
