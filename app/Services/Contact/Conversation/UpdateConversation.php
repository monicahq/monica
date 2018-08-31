<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Conversation;
use Illuminate\Database\QueryException;
use App\Models\Contact\ContactFieldType;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
            throw new \Exception('Missing parameters');
        }

        try {
            $conversation = Conversation::where('account_id', $data['account_id'])
                        ->findOrFail($data['conversation_id']);
        } catch (ModelNotFoundException $e) {
            throw $e;
        }

        try {
            ContactFieldType::where('account_id', $data['account_id'])
                                ->findOrFail($data['contact_field_type_id']);
        } catch (ModelNotFoundException $e) {
            throw $e;
        }

        try {
            $conversation->update([
                'happened_at' => $data['happened_at'],
                'contact_field_type_id' => $data['contact_field_type_id'],
            ]);
        } catch (QueryException $e) {
            throw $e;
        }

        return $conversation;
    }
}
