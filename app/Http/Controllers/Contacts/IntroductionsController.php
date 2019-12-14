<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Services\Contact\Contact\UpdateContactFirstMet;

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
     * @param Request $request
     * @param Contact $contact
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Contact $contact)
    {
        // Store the contact that allowed this encounter to happen in the first
        // place
        $contact = app(UpdateContactFirstMet::class)->execute([
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'met_through_contact_id' => $request->get('metThroughId'),
            'general_information' => $request->get('first_met_additional_info'),
            'is_date_known' => $request->is_first_met_date_known == 'known',
            'day' => $request->input('first_met_day'),
            'month' => $request->input('first_met_month'),
            'year' => $request->input('first_met_year'),
            'add_reminder' => $request->addReminder == 'on',
        ]);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.introductions_update_success'));
    }
}
