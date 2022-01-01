<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Services\Contact\Contact\UpdateContactIntroduction;
use App\Http\Resources\Contact\ContactShort as ContactResource;

class IntroductionsController extends Controller
{
    use JsonRespondController;

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Contact  $contact
     * @return \Illuminate\View\View
     */
    public function edit(Contact $contact)
    {
        $contact->throwInactive();

        $contacts = $contact->siblingContacts()
                        ->real()
                        ->active()
                        ->orderByUserPreference()
                        ->paginate(20);

        $introducer = $contact->getIntroducer();
        if ($introducer !== null) {
            $introducer = new ContactResource($introducer);
        }

        return view('people.introductions.edit')
            ->withContact($contact)
            ->withContacts(ContactResource::collection($contacts))
            ->withIntroducer($introducer);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Contact  $contact
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Contact $contact)
    {
        $contact->throwInactive();

        $contact = app(UpdateContactIntroduction::class)->execute([
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'met_through_contact_id' => $request->input('metThroughId'),
            'general_information' => $request->input('first_met_additional_info'),
            'is_date_known' => $request->input('is_first_met_date_known') == 'known',
            'day' => $request->input('first_met_day'),
            'month' => $request->input('first_met_month'),
            'year' => $request->input('first_met_year'),
            'add_reminder' => $request->addReminder == 'on',
        ]);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.introductions_update_success'));
    }
}
