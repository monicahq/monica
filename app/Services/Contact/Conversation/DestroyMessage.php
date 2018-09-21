<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Message;
use App\Exceptions\MissingParameterException;

class DestroyMessage extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'account_id',
        'conversation_id',
        'message_id',
    ];

    /**
     * Destroy a message.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        $message = Message::where('account_id', $data['account_id'])
                            ->where('conversation_id', $data['conversation_id'])
                            ->findOrFail($data['message_id']);

        $message->delete();

        return true;
    }
}
