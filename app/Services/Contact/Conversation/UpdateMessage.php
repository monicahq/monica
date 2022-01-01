<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;

class UpdateMessage extends BaseService
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
            'contact_id' => 'required|integer|exists:contacts,id',
            'conversation_id' => 'required|integer|exists:conversations,id',
            'message_id' => 'required|integer|exists:messages,id',
            'written_at' => 'required|date',
            'written_by_me' => 'required|boolean',
            'content' => 'required|string',
        ];
    }

    /**
     * Update message in a conversation.
     *
     * @param  array  $data
     * @return Message
     */
    public function execute(array $data): Message
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
                ->findOrFail($data['contact_id']);

        $contact->throwInactive();

        Conversation::where('contact_id', $data['contact_id'])
                    ->where('account_id', $data['account_id'])
                    ->findOrFail($data['conversation_id']);

        /** @var Message */
        $message = Message::where('contact_id', $data['contact_id'])
                            ->where('conversation_id', $data['conversation_id'])
                            ->where('account_id', $data['account_id'])
                            ->findOrFail($data['message_id']);

        $message->update([
            'written_at' => $data['written_at'],
            'written_by_me' => $data['written_by_me'],
            'content' => $data['content'],
        ]);

        return $message;
    }
}
