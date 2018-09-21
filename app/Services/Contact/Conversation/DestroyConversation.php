<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Conversation;
use App\Exceptions\MissingParameterException;

class DestroyConversation extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'account_id',
        'conversation_id',
    ];

    /**
     * Destroy a conversation.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        $conversation = Conversation::where('account_id', $data['account_id'])
                                    ->findOrFail($data['conversation_id']);

        $conversation->delete();

        return true;
    }
}
