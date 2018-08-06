<?php

namespace App\Http\Controllers\Api\Contact;

use Illuminate\Http\Request;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Conversation\AddMessageToConversation;
use App\Services\Contact\Conversation\UpdateMessage;
use App\Http\Resources\Conversation\Conversation as ConversationResource;

class ApiMessageController extends ApiController
{
    /**
     * Store the message.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, int $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        try {
            $message = (new AddMessageToConversation)->execute(
                $request->all()
                +
                [
                    'account_id' => auth()->user()->account->id,
                    'conversation_id' => $conversation->id,
                    'contact_id' => $conversation->contact->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (\Exception $e) {
            return $this->setHTTPStatusCode(500)
                        ->setErrorCode(41)
                        ->respondWithError(config('api.error_codes.41'));
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ConversationResource($conversation);
    }

    /**
     * Update the message.
     *
     * @param  Request $request
     * @param  int $conversationId
     * * @param  int $messageId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $conversationId, int $messageId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        try {
            $message = (new UpdateMessage)->execute(
                $request->all()
                +
                [
                    'account_id' => auth()->user()->account->id,
                    'conversation_id' => $conversationId,
                    'message_id' => $messageId,
                    'contact_id' => $conversation->contact->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (\Exception $e) {
            return $this->setHTTPStatusCode(500)
                        ->setErrorCode(41)
                        ->respondWithError(config('api.error_codes.41'));
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ConversationResource($conversation);
    }
}
