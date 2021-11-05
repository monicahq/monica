<?php

namespace App\Services\Contact\Call;

use Illuminate\Support\Arr;
use App\Models\Contact\Call;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Instance\Emotion\Emotion;

class CreateCall extends BaseService
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
            'contact_id' => 'required|integer',
            'called_at' => 'required|date',
            'content' => 'nullable|string',
            'contact_called' => 'nullable|boolean',
            'emotions' => 'nullable|array',
        ];
    }

    /**
     * Create a call.
     *
     * @param  array  $data
     * @return Call
     */
    public function execute(array $data): Call
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $contact->throwInactive();

        // emotions array is left out as they are not attached during this call
        $call = Call::create(Arr::except($data, ['emotions']));

        $this->updateLastCallInfo($contact, $call);

        if (! empty($data['emotions'])) {
            if ($data['emotions'] != '') {
                $this->addEmotions($data['emotions'], $call);
            }
        }

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
     * @param  Contact  $contact
     * @param  Call  $call
     * @return void
     */
    private function updateLastCallInfo(Contact $contact, Call $call)
    {
        if (is_null($contact->last_talked_to)) {
            $contact->last_talked_to = $call->called_at;
        } else {
            $contact->last_talked_to = $contact->last_talked_to->max($call->called_at);
        }

        $contact->save();
    }
}
