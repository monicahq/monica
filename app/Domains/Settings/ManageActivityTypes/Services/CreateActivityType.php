<?php

namespace App\Domains\Settings\ManageActivityTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ActivityType;
use App\Services\BaseService;

class CreateActivityType extends BaseService implements ServiceInterface
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
            'label' => 'required|string|max:255',
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
     * Create an activity type.
     *
     * @param  array  $data
     * @return ActivityType
     */
    public function execute(array $data): ActivityType
    {
        $this->validateRules($data);

        $type = ActivityType::create([
            'account_id' => $data['account_id'],
            'label' => $data['label'],
        ]);

        return $type;
    }
}
