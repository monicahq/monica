<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;
use App\Exceptions\MissingParameterException;

class UpdateConversation extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'happened_at',
        'contact_field_type_id',
        'account_id',
        'conversation_id',
    ];

    /**
     * Update a conversation.
     *
     * @param array $data
     * @return Conversation
     */
    public function execute(array $data): Conversation
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        $conversation = Conversation::where('account_id', $data['account_id'])
                                    ->findOrFail($data['conversation_id']);

        ContactFieldType::where('account_id', $data['account_id'])
                            ->findOrFail($data['contact_field_type_id']);

        $conversation->update([
            'happened_at' => $data['happened_at'],
            'contact_field_type_id' => $data['contact_field_type_id'],
        ]);

        return $conversation;
    }
}
