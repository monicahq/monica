<?php

namespace App\Http\Controllers\Api\Contact;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Conversation\Conversation as ConversationResource;
use App\Models\Contact\Conversation;
use App\Models\Contact\Message;
use App\Services\Contact\Conversation\AddMessageToConversation;
use App\Services\Contact\Conversation\DestroyMessage;
use App\Services\Contact\Conversation\UpdateMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiMessageController extends ApiController
{
    /**
     * Store the message.
     *
     * @param Request $request
     *
     * @return ConversationResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request, int $conversationId)
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            app(AddMessageToConversation::class)->execute(
                $request->except(['account_id', 'conversation_id', 'contact_id'])
                +
                [
                    'account_id' => auth()->user()->account_id,
                    'conversation_id' => $conversation->id,
                    'contact_id' => $conversation->contact_id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ConversationResource($conversation);
    }

    /**
     * Update the message.
     *
     * @param Request $request
     * @param int $conversationId
     * @param int $messageId
     *
     * @return ConversationResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $conversationId, int $messageId)
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);
            $message = Message::findOrFail($messageId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            app(UpdateMessage::class)->execute(
                $request->except(['account_id', 'conversation_id', 'message_id', 'contact_id'])
                +
                [
                    'account_id' => auth()->user()->account_id,
                    'conversation_id' => $conversationId,
                    'message_id' => $message->id,
                    'contact_id' => $conversation->contact_id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ConversationResource($conversation);
    }

    /**
     * Destroy the message.
     *
     * @param Request $request
     * @param int $conversationId
     * @param int $messageId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $conversationId, int $messageId)
    {
        try {
            Conversation::findOrFail($conversationId);
            Message::findOrFail($messageId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            app(DestroyMessage::class)->execute([
                'account_id' => auth()->user()->account_id,
                'conversation_id' => $conversationId,
                'message_id' => $messageId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $messageId);
    }
}
