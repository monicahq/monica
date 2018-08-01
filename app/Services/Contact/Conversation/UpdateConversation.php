<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateConversation extends BaseService
{
    private $structure = [
        'happened_at',
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
                        ->where('id', $data['conversation_id'])
                        ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw $e;
        }

        try {
            $conversation->update([
                'happened_at' => $data['happened_at'],
            ]);
        } catch (QueryException $e) {
            throw $e;
        }

        return $conversation;
    }
}
