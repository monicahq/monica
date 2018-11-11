<?php

namespace App\Http\Controllers\Api\Contact;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use App\Exceptions\MissingParameterException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Conversation\CreateConversation;
use App\Services\Contact\Conversation\UpdateConversation;
use App\Services\Contact\Conversation\DestroyConversation;
use App\Http\Resources\Conversation\Conversation as ConversationResource;

class ApiConversationController extends ApiController
{
    /**
     * Get the list of conversations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $conversations = auth()->user()->account->conversations()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return ConversationResource::collection($conversations);
    }

    /**
     * Get the list of conversations for a specific contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function conversations(Request $request, $contactId)
    {
        try {
            Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $conversations = auth()->user()->account->conversations()
                ->where('contact_id', $contactId)
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return ConversationResource::collection($conversations);
    }

    /**
     * Get the detail of a given conversation.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $conversationId)
    {
        try {
            $conversation = Conversation::where('account_id', auth()->user()->account_id)
                ->findOrFail($conversationId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new ConversationResource($conversation);
    }

    /**
     * Store the conversation.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $conversation = (new CreateConversation)->execute(
                $request->all()
                +
                [
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ConversationResource($conversation);
    }

    /**
     * Update the conversation.
     *
     * @param  Request $request
     * @param  int $conversationId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $conversationId)
    {
        try {
            $conversation = (new UpdateConversation)->execute(
                $request->all()
                +
                [
                    'account_id' => auth()->user()->account->id,
                    'conversation_id' => $conversationId,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ConversationResource($conversation);
    }

    /**
     * Destroy the conversation.
     *
     * @param  Request $request
     * @param  int $conversationId
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $conversationId)
    {
        try {
            (new DestroyConversation)->execute([
                'account_id' => auth()->user()->account->id,
                'conversation_id' => $conversationId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $conversationId);
    }
}
