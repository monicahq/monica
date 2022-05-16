<?php

namespace App\Settings\ManageCallReasons\Services;

use App\Interfaces\ServiceInterface;
use App\Models\CallReasonType;
use App\Models\User;
use App\Services\BaseService;

class CreateCallReasonType extends BaseService implements ServiceInterface
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
     * Create a call reason type.
     *
     * @param  array  $data
     * @return CallReasonType
     */
    public function execute(array $data): CallReasonType
    {
        $this->validateRules($data);

        $type = CallReasonType::create([
            'account_id' => $data['account_id'],
            'label' => $data['label'],
        ]);

        return $type;
    }
}
