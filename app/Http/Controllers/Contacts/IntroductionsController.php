<?php

namespace App\Http\Controllers\Contacts;

use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Services\Contact\Reminder\CreateReminder;
use App\Http\Requests\People\IntroductionsRequest;
use App\Services\Contact\Reminder\DestroyReminder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IntroductionsController extends Controller
{
    use JsonRespondController;

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\View\View
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
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(IntroductionsRequest $request, Contact $contact)
    {
        // Store the contact that allowed this encounter to happen in the first
        // place
        if ($request->get('metThroughId') !== null) {
            try {
                Contact::where('account_id', auth()->user()->account_id)
                    ->findOrFail($request->get('metThroughId'));
            } catch (ModelNotFoundException $e) {
                return $this->respondNotFound();
            }

            $contact->first_met_through_contact_id = $request->get('metThroughId');
        } else {
            $contact->first_met_through_contact_id = null;
        }

        try {
            app(DestroyReminder::class)->execute([
                'account_id' => $contact->account_id,
                'reminder_id' => $contact->first_met_reminder_id,
            ]);
        } catch (\Exception $e) {
        }

        if ($request->is_first_met_date_known == 'known') {
            $specialDate = $contact->setSpecialDate('first_met', $request->input('first_met_year'), $request->input('first_met_month'), $request->input('first_met_day'));

            if ($request->addReminder == 'on') {
                app(CreateReminder::class)->execute([
                    'account_id' => $contact->account_id,
                    'contact_id' => $contact->id,
                    'initial_date' => $specialDate->date->toDateString(),
                    'frequency_type' => 'year',
                    'frequency_number' => 1,
                    'title' => trans(
                        'people.introductions_reminder_title',
                        ['name' => $contact->first_name]
                    ),
                ]);
            }
        } else {
            $contact->first_met_special_date_id = null;
        }

        if ($request->first_met_additional_info != '') {
            $contact->first_met_additional_info = $request->get('first_met_additional_info');
        } else {
            $contact->first_met_additional_info = null;
        }

        $contact->save();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.introductions_update_success'));
    }
}
