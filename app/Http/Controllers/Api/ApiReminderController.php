<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Helpers\AccountHelper;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Reminder\CreateReminder;
use App\Services\Contact\Reminder\UpdateReminder;
use App\Services\Contact\Reminder\DestroyReminder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Reminder\Reminder as ReminderResource;
use App\Http\Resources\Reminder\ReminderOutbox as ReminderOutboxResource;

class ApiReminderController extends ApiController
{
    /**
     * Get the list of reminders.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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
     *
     * @param  Request  $request
     * @return ReminderResource|\Illuminate\Http\JsonResponse
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
     *
     * @param  Request  $request
     * @return ReminderResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $reminder = app(CreateReminder::class)->execute(
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

        return new ReminderResource($reminder);
    }

    /**
     * Update the reminder.
     *
     * @param  Request  $request
     * @param  int  $reminderId
     * @return ReminderResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $reminderId)
    {
        try {
            $reminder = app(UpdateReminder::class)->execute(
                $request->except(['account_id', 'reminder_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                        'reminder_id' => $reminderId,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ReminderResource($reminder);
    }

    /**
     * Delete a reminder.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $reminderId)
    {
        try {
            app(DestroyReminder::class)->execute([
                'account_id' => auth()->user()->account_id,
                'reminder_id' => $reminderId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted($reminderId);
    }

    /**
     * Get the list of reminders for the given contact.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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

    /**
     * Get the reminders for the month given in parameter.
     * - 0 means current month
     * - 1 means month+1
     * - 2 means month+2...
     *
     * @param  Request  $request
     * @param  int  $month
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function upcoming(Request $request, int $month = 0)
    {
        try {
            $reminders = AccountHelper::getUpcomingRemindersForMonth(
                auth()->user()->account,
                $month
            );
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return ReminderOutboxResource::collection($reminders);
    }
}
