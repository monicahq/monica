<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Conversation;
use App\Models\Contact\Message;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateMessage extends BaseService
{
    private $structure = [
        'message_id',
        'written_at',
        'written_by_me',
        'content',
    ];

    /**
     * Update message in a conversation.
     *
     * @param array $data
     * @return Message
     */
    public function execute(array $data): Message
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new \Exception('Missing parameters');
        }

        try {
            $conversation = Conversation::findOrFail($data['conversation_id']);
        } catch (ModelNotFoundException $e) {
            throw $e;
        }

        try {
            $message = Message::create([
                    'contact_id' => $conversation->contact_id,
                    'account_id' => $conversation->account_id,
                ]
                + $data
            );
        } catch (QueryException $e) {
            throw $e;
        }

        return $message;
    }
}
