<?php

namespace App\Services\Contact\Call;

use App\Models\Contact\Call;
use App\Services\BaseService;
use App\Models\Contact\Contact;

class DestroyCall extends BaseService
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
            'call_id' => 'required|integer',
        ];
    }

    /**
     * Destroy a call.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

        $call = Call::where('account_id', $data['account_id'])
            ->findOrFail($data['call_id']);

        $contact = $call->contact;

        // delete all associations with emotions
        $call->emotions()->sync([]);

        $call->delete();

        $this->updateLastCallInfo($contact);

        return true;
    }

    /**
     * Update last call information of the contact.
     *
     * @param Contact $contact
     * @return void
     */
    private function updateLastCallInfo(Contact $contact)
    {
        // look for all the calls of the contact and take the most recent call
        // as the one we just deleted could have been the most recent call
        $mostRecentDate = null;
        foreach ($contact->calls as $call) {
            if ($call->called_at->greaterThanOrEqualTo($mostRecentDate)) {
                $mostRecentDate = $call->called_at;
            }
        }

        if (is_null($mostRecentDate)) {
            $contact->last_talked_to = null;
        } else {
            $contact->last_talked_to = $mostRecentDate;
        }

        $contact->save();
    }
}
