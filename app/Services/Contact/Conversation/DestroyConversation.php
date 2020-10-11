<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Conversation;

class DestroyConversation extends BaseService
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
            'conversation_id' => 'required|integer|exists:conversations,id',
        ];
    }

    /**
     * Destroy a conversation.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        $conversation = Conversation::where('account_id', $data['account_id'])
                                    ->findOrFail($data['conversation_id']);

        $conversation->delete();

        return true;
    }
}
