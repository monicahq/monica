<?php

namespace App\Http\Controllers\Contacts;

use Validator;
use App\Contact;
use App\Relationship;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\RelationshipsRequest;
use App\Http\Requests\People\ExistingRelationshipsRequest;

class RelationshipsController extends Controller
{
    /**
     * Display the Create relationship page
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function new(Request $request, Contact $contact)
    {
        // getting the list of existing contacts
        $existingContacts = auth()->user()->account->contacts()
                                        ->real()
                                        ->select(['id', 'first_name', 'last_name'])
                                        ->sortedBy('name')
                                        ->get();

        // Building the list of contacts specifically for the dropdown which asks
        // for an id and a name. Also filter out the current contact.
        $arrayContacts = collect();
        foreach ($existingContacts as $existingContact) {
            if ($existingContact->id == $contact->id) {
                continue;
            }
            $arrayContacts->push([
                'id' => $existingContact->id,
                'name' => $existingContact->getCompleteName(),
            ]);
        }

        // Building the list of relationship types specifically for the dropdown which asks
        // for an id and a name.
        $arrayRelationshipTypes = collect();
        foreach (auth()->user()->account->relationshipTypes as $relationshipType) {
            $arrayRelationshipTypes->push([
                'id' => $relationshipType->id,
                'name' => $relationshipType->getLocalizedName(),
            ]);
        }

        return view('people.relationship.new')
            ->withContact($contact)
            ->withPartner(new Contact)
            ->withGenders(auth()->user()->account->genders)
            ->withRelationshipTypes($arrayRelationshipTypes)
            ->withDays(\App\Helpers\DateHelper::getListOfDays())
            ->withMonths(\App\Helpers\DateHelper::getListOfMonths())
            ->withBirthdayState($contact->getBirthdayState())
            ->withExistingContacts($arrayContacts)
            ->withType($request->get('type'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Contact $contact)
    {
        // case of linking to an existing contact
        if ($request->get('relationship_type') == 'existing') {
            $partner = Contact::findOrFail($request->get('existing_contact_id'));
            $contact->setRelationshipWith($partner, $request->get('relationship_type_id'));

            return redirect('/people/'.$contact->id)
                ->with('success', trans('people.significant_other_add_success'));
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:50',
            'last_name' => 'max:100',
            'gender_id' => 'required',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        // case of creating a new contact
        // set the name of the contact
        $partner = new Contact;
        $partner->account_id = $contact->account->id;

        if (! $partner->setName($request->input('first_name'), null, $request->input('last_name'))) {
            return back()
                ->withInput()
                ->withErrors('There has been a problem with saving the name.');
        }

        // set gender
        $partner->gender_id = $request->input('gender_id');
        $partner->save();

        // Handling the case of the birthday
        $partner->removeSpecialDate('birthdate');
        switch ($request->input('birthdate')) {
            case 'unknown':
                break;
            case 'approximate':
                $specialDate = $partner->setSpecialDateFromAge('birthdate', $request->input('age'));
                break;
            case 'almost':
                $specialDate = $partner->setSpecialDate(
                    'birthdate',
                    0,
                    $request->input('month'),
                    $request->input('day')
                );
                $newReminder = $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $partner->first_name]));
                break;
            case 'exact':
                $birthdate = $request->input('birthdayDate');
                $birthdate = new \Carbon\Carbon($birthdate);
                $specialDate = $partner->setSpecialDate(
                    'birthdate',
                    $birthdate->year,
                    $birthdate->month,
                    $birthdate->day
                );
                $newReminder = $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $partner->first_name]));
                break;
        }

        // set avatar color
        $partner->setAvatarColor();

        // create the relationship
        $contact->setRelationshipWith($partner, $request->get('relationship_type_id'));

        // check if the contact is partial
        if ($request->get('realContact')) {
            $partner->is_partial = false;
            $partner->save();
        }

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_add_success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param SignificantOther $significantOther
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact, Contact $partner)
    {
        return view('people.relationship.edit')
            ->withContact($contact)
            ->withPartner($partner)
            ->withGenders(auth()->user()->account->genders);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param RelationshipsRequest $request
     * @param Contact $contact
     * @param SignificantOther $significantOther
     * @return \Illuminate\Http\Response
     */
    public function update(RelationshipsRequest $request, Contact $contact, Contact $partner)
    {
        $partner->update(
            $request->only([
                'first_name',
                'last_name',
                'gender_id',
            ])
            + [
                'account_id' => $contact->account_id,
            ]
        );

        if ($request->get('realContact')) {
            $partner->update([
                'is_partial' => 0,
                ]
            );

            $contact->updateRelationshipWith($partner);
        }

        // birthdate
        $partner->removeSpecialDate('birthdate');
        switch ($request->input('birthdate')) {
            case 'approximate':
                $specialDate = $partner->setSpecialDateFromAge('birthdate', $request->input('age'));
                break;
            case 'exact':
                $specialDate = $partner->setSpecialDate('birthdate', $request->input('birthdate_year'), $request->input('birthdate_month'), $request->input('birthdate_day'));
                $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $partner->first_name]));
                break;
            case 'unknown':
            default:
                break;
        }

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_edit_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param SignificantOther $significantOther
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Contact $partner)
    {
        if ($contact->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        if ($partner->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        if ($partner->reminders) {
            $partner->reminders()->get()->each->delete();
        }

        $contact->unsetRelationshipWith($partner);

        $partner->specialDates->each->delete();
        $partner->delete();

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_delete_success'));
    }

    /**
     * Unlink the relationship between those two people.
     *
     * @param  Contact $contact
     * @param  Contact $partner
     * @return
     */
    public function unlink(Contact $contact, Contact $partner)
    {
        if ($contact->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        if ($partner->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        $contact->unsetRelationshipWith($partner, true);

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_delete_success'));
    }
}
