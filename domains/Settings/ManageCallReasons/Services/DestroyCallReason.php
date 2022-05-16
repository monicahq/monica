<?php

namespace App\Settings\ManageCallReasons\Services;

use App\Interfaces\ServiceInterface;
use App\Models\CallReason;
use App\Models\CallReasonType;
use App\Models\User;
use App\Services\BaseService;

class DestroyCallReason extends BaseService implements ServiceInterface
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
            'call_reason_type_id' => 'required|integer|exists:call_reason_types,id',
            'call_reason_id' => 'required|integer|exists:call_reasons,id',
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
     * Destroy a call reason.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        CallReasonType::where('account_id', $data['account_id'])
            ->findOrFail($data['call_reason_type_id']);

        $reason = CallReason::where('call_reason_type_id', $data['call_reason_type_id'])
            ->findOrFail($data['call_reason_id']);

        $reason->delete();
    }
}
