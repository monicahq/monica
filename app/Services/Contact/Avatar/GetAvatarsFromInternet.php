<?php

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;

class GetAvatarsFromInternet extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'contact_id' => 'required|integer|exists:contacts,id',
        ];
    }

    /**
     * Query both Gravatar and Adorable Avatars based on the email address of
     * the contact.
     *
     * - http://avatars.adorable.io/ gives avatars based on a random string.
     * - Gravatar only gives an avatar only if it's set.
     *
     * @param array $data
     * @return Message
     */
    public function execute(array $data): Message
    {
        $this->validate($data);

        Contact::where('account_id', $data['account_id'])
                ->findOrFail($data['contact_id']);

        Conversation::where('contact_id', $data['contact_id'])
                    ->where('account_id', $data['account_id'])
                    ->findOrFail($data['conversation_id']);

        return Message::create($data);
    }
}
