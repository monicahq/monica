<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Models\Contact\Conversation;
use App\Services\Contact\Conversation\DestroyMessage;
use App\Services\Contact\Conversation\CreateConversation;
use App\Services\Contact\Conversation\UpdateConversation;
use App\Services\Contact\Conversation\DestroyConversation;
use App\Services\Contact\Conversation\AddMessageToConversation;

class ConversationsController extends Controller
{
    /**
     * Display the Create conversation page.
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function new(Request $request, Contact $contact)
    {
        return view('people.conversations.new')
            ->withContact($contact)
            ->withLocale(auth()->user()->locale)
            ->withContactFieldTypes(auth()->user()->account->contactFieldTypes);
    }

    /**
     * Display the list of conversations.
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Contact $contact)
    {
        $conversationsCollection = collect([]);
        $conversations = $contact->conversations()->get();

        foreach ($conversations as $conversation) {
            $data = [
                'id' => $conversation->id,
                'message_count' => $conversation->messages->count(),
                'contact_field_type' => $conversation->contactFieldType->name,
                'icon' => $conversation->contactFieldType->fontawesome_icon,
                'content' => str_limit($conversation->messages->last()->content, 50),
                'happened_at' => DateHelper::getShortDate($conversation->happened_at),
                'route' => route('people.conversation.edit', [$contact, $conversation]),
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Contact $contact)
    {
        // find out what the date is
        $chosenDate = $request->get('conversationDateRadio');
        if ($chosenDate == 'today') {
            $date = now()->format('Y-m-d');
        } elseif ($chosenDate == 'yesterday') {
            $date = now()->subDay()->format('Y-m-d');
        } else {
            $date = $request->get('conversationDate');
        }

        $data = [
            'happened_at' => $date,
            'account_id' => auth()->user()->account->id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $request->get('contactFieldTypeId'),
        ];

        // create the conversation
        try {
            $conversation = (new CreateConversation)->execute($data);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(trans('app.error_save'));
        }

        // add the messages to the conversation
        $messages = explode(',', $request->get('messages'));
        foreach ($messages as $messageId) {
            $data = [
                'account_id' => auth()->user()->account->id,
                'conversation_id' => $conversation->id,
                'contact_id' => $conversation->contact->id,
                'written_at' => $date,
                'written_by_me' => ($request->get('who_wrote_'.$messageId) == 'me'),
                'content' => $request->get('content_'.$messageId),
            ];

            try {
                (new AddMessageToConversation)->execute($data);
            } catch (\Exception $e) {
                return back()
                    ->withInput()
                    ->withErrors(trans('app.error_save'));
            }
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.relationship_form_add_success'));
    }

    /**
     * Display a specific conversation.
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
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
            ->withLocale(auth()->user()->locale)
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
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contact $contact, Conversation $conversation)
    {
        // find out what the date is
        $chosenDate = $request->get('conversationDateRadio');
        if ($chosenDate == 'today') {
            $date = now()->format('Y-m-d');
        } elseif ($chosenDate == 'yesterday') {
            $date = now()->subDay()->format('Y-m-d');
        } else {
            $date = $request->get('conversationDate');
        }

        $data = [
            'happened_at' => $date,
            'account_id' => auth()->user()->account->id,
            'conversation_id' => $conversation->id,
            'contact_field_type_id' => $request->get('contactFieldTypeId'),
        ];

        // update the conversation
        try {
            $conversation = (new UpdateConversation)->execute($data);
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
            (new DestroyMessage)->execute($data);
        }

        // and create all new ones
        $messages = explode(',', $request->get('messages'));
        foreach ($messages as $messageId) {
            $data = [
                'account_id' => auth()->user()->account->id,
                'conversation_id' => $conversation->id,
                'contact_id' => $conversation->contact->id,
                'written_at' => $date,
                'written_by_me' => ($request->get('who_wrote_'.$messageId) == 'me'),
                'content' => $request->get('content_'.$messageId),
            ];

            try {
                (new AddMessageToConversation)->execute($data);
            } catch (\Exception $e) {
                return back()
                    ->withInput()
                    ->withErrors(trans('app.error_save'));
            }
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.relationship_form_add_success'));
    }

    /**
     * Delete the conversation.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Conversation $conversation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Contact $contact, Conversation $conversation)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'conversation_id' => $conversation->id,
        ];

        try {
            (new DestroyConversation)->execute($data);
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.conversation_delete_success'));
    }
}
