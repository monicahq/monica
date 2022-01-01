<?php

namespace App\Services\Contact\Call;

use App\Models\Contact\Call;
use App\Services\BaseService;
use App\Models\Instance\Emotion\Emotion;

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
            'emotions' => 'nullable|array',
        ];
    }

    /**
     * Update a call.
     *
     * @param  array  $data
     * @return Call
     */
    public function execute(array $data): Call
    {
        $this->validate($data);

        /** @var Call */
        $call = Call::where('account_id', $data['account_id'])
            ->findOrFail($data['call_id']);

        $call->contact->throwInactive();

        $call->update([
            'called_at' => $data['called_at'],
            'content' => (empty($data['content']) ? null : $data['content']),
            'contact_called' => (empty($data['contact_called']) ? null : $data['contact_called']),
        ]);

        // emotions array is left out as they are not attached during this call
        if (! empty($data['emotions'])) {
            if ($data['emotions'] != '') {
                $this->addEmotions($data['emotions'], $call);
            }
        }

        $this->updateLastCallInfo($call);

        return $call;
    }

    /**
     * Add emotions to the call.
     *
     * @param  array  $emotions
     * @param  Call  $call
     * @return void
     */
    private function addEmotions(array $emotions, Call $call)
    {
        // reset current emotions
        $call->emotions()->sync([]);

        // saving new emotions
        foreach ($emotions as $emotionId) {
            $emotion = Emotion::findOrFail($emotionId);
            $call->emotions()->syncWithoutDetaching([$emotion->id => [
                'account_id' => $call->account_id,
                'contact_id' => $call->contact_id,
            ]]);
        }
    }

    /**
     * Update last call information of the contact.
     *
     * @param  Call  $call
     * @return void
     */
    private function updateLastCallInfo(Call $call)
    {
        /** @var \App\Models\Contact\Contact */
        $contact = $call->contact;
        if (is_null($contact->last_talked_to)) {
            $contact->last_talked_to = $call->called_at;
        } else {
            $contact->last_talked_to = $contact->last_talked_to->max($call->called_at);
        }

        $contact->save();
    }
}
