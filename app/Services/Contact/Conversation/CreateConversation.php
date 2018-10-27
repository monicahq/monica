<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact\Conversation;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;
use App\Exceptions\MissingParameterException;

class CreateConversation extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'happened_at',
        'account_id',
        'contact_id',
        'contact_field_type_id',
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
            throw new MissingParameterException('Missing parameters');
        }

        Contact::where('account_id', $data['account_id'])
                ->findOrFail($data['contact_id']);

        ContactFieldType::where('account_id', $data['account_id'])
                        ->findOrFail($data['contact_field_type_id']);

        return Conversation::create($data);
    }
}
