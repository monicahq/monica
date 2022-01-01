<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;

class UpdateConversation extends BaseService
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
            'happened_at' => 'required|date',
            'contact_field_type_id' => 'required|integer',
            'conversation_id' => 'required|integer|exists:conversations,id',
        ];
    }

    /**
     * Update a conversation.
     *
     * @param  array  $data
     * @return Conversation
     */
    public function execute(array $data): Conversation
    {
        $this->validate($data);

        /** @var Conversation */
        $conversation = Conversation::where('account_id', $data['account_id'])
                                    ->findOrFail($data['conversation_id']);

        $conversation->contact->throwInactive();

        ContactFieldType::where('account_id', $data['account_id'])
                            ->findOrFail($data['contact_field_type_id']);

        $conversation->update([
            'happened_at' => $data['happened_at'],
            'contact_field_type_id' => $data['contact_field_type_id'],
        ]);

        return $conversation;
    }
}
