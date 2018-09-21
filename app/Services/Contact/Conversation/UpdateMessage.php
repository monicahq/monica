<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use App\Exceptions\MissingParameterException;

class UpdateMessage extends BaseService
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
            throw new MissingParameterException('Missing parameters');
        }

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
