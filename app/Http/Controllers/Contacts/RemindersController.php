<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



namespace App\Http\Controllers\Contacts;

use App\Helpers\AvatarHelper;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\RemindersRequest;

class RemindersController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function create(Contact $contact)
    {
        return view('people.reminders.add')
            ->withContact($contact)
            ->withAvatar(AvatarHelper::get($contact, 87))
            ->withReminder(new Reminder);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param RemindersRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(RemindersRequest $request, Contact $contact)
    {
        $reminder = $contact->reminders()->create(
            $request->only([
                'title',
                'description',
                'frequency_type',
                'next_expected_date',
                'frequency_number',
            ])
            + ['account_id' => $contact->account_id]
        );

        $reminder->scheduleNotifications();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.reminders_create_success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Reminder $reminder
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact, Reminder $reminder)
    {
        return view('people.reminders.edit')
            ->withContact($contact)
            ->withAvatar(AvatarHelper::get($contact, 87))
            ->withReminder($reminder);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param RemindersRequest $request
     * @param Contact $contact
     * @param Reminder $reminder
     * @return \Illuminate\Http\Response
     */
    public function update(RemindersRequest $request, Contact $contact, Reminder $reminder)
    {
        $reminder->update(
            $request->only([
                'title',
                'next_expected_date',
                'description',
                'frequency_type',
                'frequency_number',
            ])
            + ['account_id' => $contact->account_id]
        );

        $reminder->purgeNotifications();
        $reminder->scheduleNotifications();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.reminders_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Reminder $reminder
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Reminder $reminder)
    {
        if ($reminder->account_id != auth()->user()->account_id) {
            return redirect()->route('people.index');
        }

        $reminder->purgeNotifications();
        $reminder->delete();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.reminders_delete_success'));
    }
}
