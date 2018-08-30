<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddMessageToConversation extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'account_id',
        'contact_id',
        'conversation_id',
        'written_at',
        'written_by_me',
        'content',
    ];

    /**
     * Add message to a conversation.
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
            Conversation::where('contact_id', $data['contact_id'])
                                ->where('account_id', $data['account_id'])
                                ->findOrFail($data['conversation_id']);
        } catch (ModelNotFoundException $e) {
            throw $e;
        }

        try {
            $message = Message::create($data);
        } catch (QueryException $e) {
            throw $e;
        }

        return $message;
    }
}
