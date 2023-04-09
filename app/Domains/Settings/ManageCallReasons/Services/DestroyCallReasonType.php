<?php

namespace App\Domains\Settings\ManageCallReasons\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyCallReasonType extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'call_reason_type_id' => 'required|integer|exists:call_reason_types,id',
            'author_id' => 'required|uuid|exists:users,id',
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
     * Destroy a call reason type.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $type = $this->account()->callReasonTypes()
            ->findOrFail($data['call_reason_type_id']);

        $type->delete();
    }
}
