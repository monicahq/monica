<?php

namespace App\Http\Controllers\Contacts;

use App\Gift;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IntroductionsRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        return view('people.introductions.edit')
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
        if ($request->get('metThroughId') !== null) {
            try {
                Contact::where('account_id', auth()->user()->account_id)
                    ->where('id', $request->get('metThroughId'))
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->respondNotFound();
            }

            $contact->first_met_through_contact_id = $request->get('metThroughId');
        } else {
            $contact->first_met_through_contact_id = null;
        }

        $contact->removeSpecialDate('first_met');

        if ($request->is_first_met_date_known == 'known') {
            $specialDate = $contact->setSpecialDate('first_met', $request->input('first_met_year'), $request->input('first_met_month'), $request->input('first_met_day'));

            if ($request->addReminder == 'on') {
                $specialDate->setReminder('year', 1, trans('people.introductions_reminder_title', ['name' => $contact->first_name]));
            }
        }

        if ($request->first_met_additional_info != '') {
            $contact->first_met_additional_info = $request->get('first_met_additional_info');
        } else {
            $contact->first_met_additional_info = null;
        }

        $contact->save();

        $contact->logEvent('contact', $contact->id, 'update');

        return redirect('/people/'.$contact->hashID())
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

        return redirect('/people/'.$contact->hashID())
            ->with('success', trans('people.gifts_delete_success'));
    }
}
