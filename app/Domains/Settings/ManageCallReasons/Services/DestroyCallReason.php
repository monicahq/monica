<?php

namespace App\Domains\Settings\ManageCallReasons\Services;

use App\Interfaces\ServiceInterface;
use App\Services\BaseService;

class DestroyCallReason extends BaseService implements ServiceInterface
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
            'call_reason_id' => 'required|integer|exists:call_reasons,id',
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
     * Destroy a call reason.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $reasonType = $this->account()->callReasonTypes()
            ->findOrFail($data['call_reason_type_id']);

        $reason = $reasonType->callReasons()
            ->findOrFail($data['call_reason_id']);

        $reason->delete();
    }
}
