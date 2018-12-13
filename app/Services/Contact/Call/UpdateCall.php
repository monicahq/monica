<?php

namespace App\Services\Contact\Call;

use App\Models\Contact\Call;
use App\Services\BaseService;

class UpdateCall extends BaseService
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
            'call_id' => 'required|integer|exists:calls,id',
            'called_at' => 'required|date',
            'content' => 'nullable|string',
            'contact_called' => 'nullable|boolean',
        ];
    }

    /**
     * Update a call.
     *
     * @param array $data
     * @return Call
     */
    public function execute(array $data) : Call
    {
        $this->validate($data);

        $call = Call::where('account_id', $data['account_id'])
            ->findOrFail($data['call_id']);

        $call->update([
            'called_at' => $data['called_at'],
            'content' => (empty($data['content']) ? null : $data['content']),
            'contact_called' => (empty($data['contact_called']) ? null : $data['contact_called']),
        ]);

        $this->updateLastCallInfo($call);

        return $call;
    }

    /**
     * Update last call information of the contact.
     *
     * @param Call $call
     * @return void
     */
    private function updateLastCallInfo(Call $call)
    {
        if (is_null($call->contact->last_talked_to)) {
            $call->contact->last_talked_to = $call->called_at;
        } else {
            $call->contact->last_talked_to = $call->contact->last_talked_to->max($call->called_at);
        }

        $call->contact->save();
    }
}
