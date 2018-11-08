<?php

namespace App\Http\Controllers\Api;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Models\Contact\Reminder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
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
        try {
            $reminders = auth()->user()->account->reminders()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

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
        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $reminder = Reminder::create(
                $request->all()
                + ['account_id' => auth()->user()->account_id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

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

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $reminder->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new ReminderResource($reminder);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request $request
     * @return mixed
     */
    private function validateUpdate(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:100000',
            'description' => 'required|max:1000000',
            'next_expected_date' => 'required|date',
            'frequency_type' => [
                'required',
                Rule::in(Reminder::$frequencyTypes),
            ],
            'frequency_number' => 'integer',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->respondValidatorFailed($validator);
        }

        $date = DateHelper::parseDate($request->get('next_expected_date'));
        if ($date->isPast()) {
            return $this->setErrorCode(38)
                        ->respondWithError(config('api.error_codes.38'));
        }

        try {
            Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return true;
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
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());

        return ReminderResource::collection($reminders);
    }
}
