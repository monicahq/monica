<?php

namespace App\Domains\Settings\ManageCallReasons\Services;

use App\Interfaces\ServiceInterface;
use App\Models\CallReasonType;
use App\Services\BaseService;

class UpdateCallReasonType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'call_reason_type_id' => 'required|integer|exists:call_reason_types,id',
            'label' => 'required|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Update a call reason type.
     */
    public function execute(array $data): CallReasonType
    {
        $this->validateRules($data);

        $type = $this->account()->callReasonTypes()
            ->findOrFail($data['call_reason_type_id']);

        $type->label = $data['label'];
        $type->save();

        return $type;
    }
}
