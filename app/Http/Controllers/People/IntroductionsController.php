<?php

namespace App\Http\Controllers\People;

use App\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IntroductionsRequest;

class IntroductionsController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Gift $gift
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact)
    {
        return view('people.dashboard.introductions.edit')
            ->withContact($contact);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param IntroductionsRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function update(IntroductionsRequest $request, Contact $contact)
    {
        // Store the contact that allowed this encounter to happen in the first
        // place
        if ($request->get('metThroughId') != 0) {
            try {
                $metThroughContact = Contact::where('account_id', auth()->user()->account_id)
                    ->where('id', $request->get('metThroughId'))
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->respondNotFound();
            }

            $contact->first_met_through_contact_id = $request->get('metThroughId');
        } else {
            $contact->first_met_through_contact_id = null;
        }

        if ($request->is_first_met_date_known == 'known') {
            $contact->first_met = $request->get('first_met');
        } else {
            $contact->first_met = null;
        }

        if ($request->first_met_additional_info != '') {
            $contact->first_met_additional_info = $request->get('first_met_additional_info');
        } else {
            $contact->first_met_additional_info = null;
        }

        if ($request->addReminder == 'on') {
            $reminder = $contact->reminders()->create(
                [
                    'account_id' => $contact->account_id,
                    'title' => trans('people.introductions_reminder_title'),
                    'description' => '',
                    'frequency_type' => 'year',
                    'next_expected_date' => $request->get('first_met'),
                    'frequency_number' => 1,
                ]
            );

            $reminder->calculateNextExpectedDate(auth()->user()->timezone);
            $reminder->save();
        }

        $contact->save();

        $contact->logEvent('contact', $contact->id, 'update');

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.introductions_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Gift $gift
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Gift $gift)
    {
        $gift->delete();

        $contact->events()->forObject($gift)->get()->each->delete();

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.gifts_delete_success'));
    }
}
