<?php

namespace App\Http\Controllers\Api\Contact;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Conversation\Conversation as ConversationResource;
use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;
use App\Services\Contact\Conversation\CreateConversation;
use App\Services\Contact\Conversation\DestroyConversation;
use App\Services\Contact\Conversation\UpdateConversation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiConversationController extends ApiController
{
    /**
     * Get the list of conversations.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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
     * @param Request $request
     *
     * @return ConversationResource|\Illuminate\Http\JsonResponse
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
     * @param Request $request
     *
     * @return ConversationResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $conversation = app(CreateConversation::class)->execute(
                $request->except(['account_id'])
                +
                [
                    'account_id' => auth()->user()->account_id,
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
     * Update the conversation.
     *
     * @param Request $request
     * @param int $conversationId
     *
     * @return ConversationResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $conversationId)
    {
        try {
            $conversation = app(UpdateConversation::class)->execute(
                $request->except(['account_id', 'conversation_id'])
                +
                [
                    'account_id' => auth()->user()->account_id,
                    'conversation_id' => $conversationId,
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
     * Destroy the conversation.
     *
     * @param Request $request
     * @param int $conversationId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $conversationId)
    {
        try {
            app(DestroyConversation::class)->execute([
                'account_id' => auth()->user()->account_id,
                'conversation_id' => $conversationId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $conversationId);
    }
}
