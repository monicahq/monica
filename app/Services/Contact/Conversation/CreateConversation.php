<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateConversation extends BaseService
{
    private $structure = [
        'happened_at',
        'account_id',
        'contact_id',
        'contact_field_type',
    ];

    /**
     * Create a conversation.
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
            $contact = Contact::where('account_id', $data['account_id'])
                        ->where('id', $data['contact_id'])
                        ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw $e;
        }

        try {
            $conversation = Conversation::create($data);
        } catch (QueryException $e) {
            throw $e;
        }

        return $conversation;
    }
}
