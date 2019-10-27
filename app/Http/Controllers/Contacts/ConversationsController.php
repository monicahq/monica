<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Models\Contact\Conversation;
use App\Traits\JsonRespondController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Conversation\DestroyMessage;
use App\Services\Contact\Conversation\CreateConversation;
use App\Services\Contact\Conversation\UpdateConversation;
use App\Services\Contact\Conversation\DestroyConversation;
use App\Services\Contact\Conversation\AddMessageToConversation;

class ConversationsController extends Controller
{
    use JsonRespondController;

    /**
     * Display the Create conversation page.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request, Contact $contact)
    {
        return view('people.conversations.new')
            ->withContact($contact)
            ->withContactFieldTypes(auth()->user()->account->contactFieldTypes);
    }

    /**
     * Display the list of conversations.
     *
     * @param  Contact $contact
     * @return Collection
     */
    public function index(Request $request, Contact $contact)
    {
        $conversationsCollection = collect([]);
        $conversations = $contact->conversations()->get();

        foreach ($conversations as $conversation) {
            $message = $conversation->messages->last();
            $data = [
                'id' => $conversation->id,
                'message_count' => $conversation->messages->count(),
                'contact_field_type' => $conversation->contactFieldType->name,
                'icon' => $conversation->contactFieldType->fontawesome_icon,
                'content' => ! is_null($message) ? mb_strimwidth($message->content, 0, 50, '…') : '',
                'happened_at' => DateHelper::getShortDate($conversation->happened_at),
                'route' => route('people.conversations.edit', [$contact, $conversation]),
            ];
            $conversationsCollection->push($data);
        }

        return $conversationsCollection;
    }

    /**
     * Store the conversation.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Contact $contact)
    {
        $data = $this->validateAndGetDatas($request);

        if ($data instanceof \Illuminate\Contracts\Validation\Validator) {
            return back()
                ->withInput()
                ->withErrors($data);
        }

        $date = $data['happened_at'];
        $data['contact_id'] = $contact->id;

        // create the conversation
        try {
            $conversation = app(CreateConversation::class)->execute($data);
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->validator);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(trans('app.error_save'));
        }

        // add the messages to the conversation
        $result = $this->updateMessages($request, $conversation, $date);
        if ($result !== true) {
            return back()
                ->withInput()
                ->withErrors($result);
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.conversation_add_success'));
    }

    /**
     * Display a specific conversation.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\View\View
     */
    public function edit(Request $request, Contact $contact, Conversation $conversation)
    {
        // preparing the messages for the Vue component
        $messages = collect([]);
        foreach ($conversation->messages as $message) {
            $messages->push([
                'uid' => $message->id,
                'content' => $message->content,
                'author' => ($message->written_by_me ? 'me' : 'other'),
            ]);
        }

        return view('people.conversations.edit')
            ->withContact($contact)
            ->withConversation($conversation)
            ->withMessages($messages)
            ->withContactFieldTypes(auth()->user()->account->contactFieldTypes);
    }

    /**
     * Update the conversation.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Conversation $conversation
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Contact $contact, Conversation $conversation)
    {
        $data = $this->validateAndGetDatas($request);

        if ($data instanceof \Illuminate\Contracts\Validation\Validator) {
            return back()
                ->withInput()
                ->withErrors($data);
        }

        $date = $data['happened_at'];
        $data['conversation_id'] = $conversation->id;

        // update the conversation
        try {
            $conversation = app(UpdateConversation::class)->execute($data);
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->validator);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(trans('app.error_save'));
        }

        // delete all current messages
        foreach ($conversation->messages as $message) {
            $data = [
                'account_id' => auth()->user()->account->id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
            ];
            app(DestroyMessage::class)->execute($data);
        }

        // and create all new ones
        $result = $this->updateMessages($request, $conversation, $date);
        if ($result !== true) {
            return back()
                ->withInput()
                ->withErrors($result);
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.conversation_edit_success'));
    }

    /**
     * Validate datas and get an array for create or update a conversation.
     *
     * @param Request $request
     * @return array|\Illuminate\Contracts\Validation\Validator
     */
    private function validateAndGetDatas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversationDateRadio' => 'required',
            'conversationDate' => 'required_unless:conversationDateRadio,today,yesterday',
            'messages' => 'required',
            'contactFieldTypeId' => 'required|integer|exists:contact_field_types,id',
        ], [
            'messages.required' => trans('people.conversation_add_error'),
        ]);

        if ($validator->fails()) {
            return $validator;
        }

        // find out what the date is
        $chosenDate = $request->get('conversationDateRadio');
        if ($chosenDate == 'today') {
            $date = now()->format('Y-m-d');
        } elseif ($chosenDate == 'yesterday') {
            $date = now()->subDay()->format('Y-m-d');
        } else {
            $date = $request->get('conversationDate');
        }

        return [
            'account_id' => auth()->user()->account_id,
            'happened_at' => $date,
            'contact_field_type_id' => $request->get('contactFieldTypeId'),
        ];
    }

    /**
     * Update messages for conversation.
     *
     * @param Request $request
     * @param Conversation $conversation
     * @param string $date
     * @return bool|string|\Illuminate\Contracts\Validation\Validator
     */
    private function updateMessages(Request $request, Conversation $conversation, string $date)
    {
        $messages = explode(',', $request->get('messages'));
        foreach ($messages as $messageId) {
            $data = [
                'account_id' => auth()->user()->account->id,
                'conversation_id' => $conversation->id,
                'contact_id' => $conversation->contact->id,
                'written_at' => $date,
                'written_by_me' => ($request->get('who_wrote_'.$messageId) === 'me'),
                'content' => $request->get('content_'.$messageId),
            ];

            try {
                app(AddMessageToConversation::class)->execute($data);
            } catch (ValidationException $e) {
                return $e->validator;
            } catch (\Exception $e) {
                return trans('app.error_save');
            }
        }

        return true;
    }

    /**
     * Delete the conversation.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Conversation $conversation
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Contact $contact, Conversation $conversation)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'conversation_id' => $conversation->id,
        ];

        try {
            app(DestroyConversation::class)->execute($data);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.conversation_delete_success'));
    }
}
