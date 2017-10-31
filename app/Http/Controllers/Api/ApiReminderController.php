<?php

namespace App\Http\Controllers\Api;

use Validator;
use App\Contact;
use App\Reminder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Reminder\Reminder as ReminderResource;

class ApiReminderController extends ApiController
{
    /**
     * Get the list of reminders.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $reminders = auth()->user()->account->reminders()
                                ->paginate($this->getLimitPerPage());

        return ReminderResource::collection($reminders);
    }

    /**
     * Get the detail of a given reminder.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $reminderId)
    {
        try {
            $reminder = Reminder::where('account_id', auth()->user()->account_id)
                ->where('id', $reminderId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new ReminderResource($reminder);
    }

    /**
     * Store the reminder.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:100000',
            'description' => 'max:1000000',
            'frequency_type' => [
                'required',
                Rule::in(['one_time', 'day', 'month', 'year']),
            ],
            'frequency_number' => 'integer',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $reminder = Reminder::create($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $reminder->account_id = auth()->user()->account->id;
        $reminder->save();

        return new ReminderResource($reminder);
    }

    /**
     * Update the reminder.
     * @param  Request $request
     * @param  int $reminderId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $reminderId)
    {
        try {
            $reminder = Reminder::where('account_id', auth()->user()->account_id)
                ->where('id', $reminderId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:100000',
            'description' => 'required|max:1000000',
            'frequency_type' => [
                'required',
                Rule::in(['one_time', 'day', 'month', 'year']),
            ],
            'frequency_number' => 'integer',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $reminder->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new ReminderResource($reminder);
    }

    /**
     * Delete a reminder.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $reminderId)
    {
        try {
            $reminder = Reminder::where('account_id', auth()->user()->account_id)
                ->where('id', $reminderId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $reminder->delete();

        return $this->respondObjectDeleted($reminder->id);
    }

    /**
     * Get the list of calls for the given contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function reminders(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $reminders = $contact->reminders()
                ->paginate($this->getLimitPerPage());

        return ReminderResource::collection($reminders);
    }
}
