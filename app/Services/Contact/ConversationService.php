<?php

namespace App\Services\Contact;

use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;

class ConversationService
{
    /**
     * Create a conversation.
     *
     * @param  ConversationRequest $request
     * @param  Account             $account
     * @param  int             $contactId
     * @return Conversation
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

        return $conversation;
    }

    /**
     * Edit a conversation.
     *
     * @param  ConversationRequest $request
     * @param  Contact             $contact
     * @return Conversation
     */
    public function update(ConversationRequest $request, Conversation $conversation)
    {
        try {
            $conversation->update($request->all());
        } catch (QueryException $e) {
            throw $e;
        }

        return $conversation;
    }

    /**
     * Delete a conversation.
     *
     * @param  ConversationRequest $request
     * @param  Contact             $contact
     * @return bool
     */
    public function destroy(Conversation $conversation)
    {
        try {
            $conversation->delete();
        } catch (QueryException $e) {
            throw $e;
        }

        return true;
    }
}
