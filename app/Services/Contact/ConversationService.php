<?php

namespace App\Services\Contact;

use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;
use App\Models\Contact\Message;
use App\Http\Resources\Conversation\Conversation as ConversationResource;

class ConversationService
{
    /**
     * Create a conversation.
     *
     * @param  ConversationRequest $request
     * @param  Account             $account
     * @param  int             $contactId
     * @return Illuminate\Http\Resources\Json\Resource
     */
    public function create(ConversationRequest $request, Account $account, int $contactId)
    {
        try {
            $contact = Contact::where('account_id', $account->id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw $e;
        }

        try {
            $conversation = Conversation::create(
                $request->all()
                + [
                    'account_id' => $account->id,
                    'contact_id' => $contact->id,
                ]
            );
        } catch (QueryException $e) {
            throw $e;
        }

        return new ConversationResource($conversation);
    }

    /**
     * Edit a conversation.
     *
     * @param  ConversationRequest $request
     * @param  Contact             $contact
     * @return Illuminate\Http\Resources\Json\Resource
     */
    public function update(ConversationRequest $request, Conversation $conversation)
    {
        try {
            $conversation->update($request->all());
        } catch (QueryException $e) {
            throw $e;
        }

        return new ConversationResource($conversation);
    }
}
