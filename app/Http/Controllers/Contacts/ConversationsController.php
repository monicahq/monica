<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Services\Contact\Conversation\CreateConversation;
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
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (\Exception $e) {
            return $this->setHTTPStatusCode(500)
                ->setErrorCode(41)
                ->respondWithError(config('api.error_codes.41'));
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        // add the messages to the conversation
        $messages = explode(',', $request->get('messages'));
        foreach ($messages as $messageId) {
            $data = [
                'account_id' => auth()->user()->account->id,
                'conversation_id' => $conversation->id,
                'contact_id' => $conversation->contact->id,
                'written_at' => $date,
                'written_by_me' => ($request->get('who_wrote_'.$messageId) == 'me' ? true : false),
                'content' => $request->get('content_'.$messageId),
            ];

            try {
                $message = (new AddMessageToConversation)->execute($data);
            } catch (ModelNotFoundException $e) {
                return $this->respondNotFound();
            } catch (\Exception $e) {
                return $this->setHTTPStatusCode(500)
                    ->setErrorCode(41)
                    ->respondWithError(config('api.error_codes.41'));
            } catch (QueryException $e) {
                return $this->respondInvalidQuery();
            }
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.relationship_form_add_success'));
    }
}
