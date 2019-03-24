<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Models\Relationship\Relationship;
use Illuminate\Support\Facades\Validator;
use App\Services\Contact\Contact\UpdateBirthdayInformation;

class RelationshipsController extends Controller
{
    /**
     * Display the Create relationship page.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request, Contact $contact)
    {
        // getting top 100 of existing contacts
        $existingContacts = auth()->user()->account->contacts()
                                    ->real()
                                    ->active()
                                    ->select(['id', 'first_name', 'last_name', 'middle_name', 'nickname'])
                                    ->sortedBy('name')
                                    ->take(100)
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
                'complete_name' => $existingContact->name,
            ]);
        }

        // Building the list of relationship types specifically for the dropdown which asks
        // for an id and a name.
        $arrayRelationshipTypes = collect();
        foreach (auth()->user()->account->relationshipTypes as $relationshipType) {
            $arrayRelationshipTypes->push([
                'id' => $relationshipType->id,
                'name' => $relationshipType->getLocalizedName($contact, true),
            ]);
        }

        return view('people.relationship.new')
            ->withContact($contact)
            ->withPartner(new Contact)
            ->withGenders(auth()->user()->account->genders)
            ->withRelationshipTypes($arrayRelationshipTypes)
            ->withDays(DateHelper::getListOfDays())
            ->withMonths(DateHelper::getListOfMonths())
            ->withBirthdate(now(DateHelper::getTimezone())->toDateString())
            ->withExistingContacts($arrayContacts)
            ->withType($request->get('type'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Contact $contact)
    {
        // case of linking to an existing contact
        if ($request->get('relationship_type') == 'existing') {
            $validator = Validator::make($request->all(), [
                'existing_contact_id' => 'required|integer',
                'relationship_type_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return back()
                    ->withInput()
                    ->withErrors($validator);
            }
            $partner = Contact::where('account_id', $request->user()->account_id)
                ->findOrFail($request->get('existing_contact_id'));
            $contact->setRelationship($partner, $request->get('relationship_type_id'));

            return redirect()->route('people.show', $contact)
                ->with('success', trans('people.relationship_form_add_success'));
        }

        // case of creating a new contact
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:50',
            'last_name' => 'max:100',
            'gender_id' => 'required|integer',
            'birthdayDate' => 'date_format:Y-m-d',
            'relationship_type_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        // set the name of the contact
        $partner = new Contact;
        $partner->account_id = $contact->account->id;
        // set gender
        $partner->gender_id = $request->input('gender_id');
        $partner->is_partial = true;

        if (! $partner->setName($request->input('first_name'), $request->input('last_name'))) {
            return back()
                ->withInput()
                ->withErrors('There has been a problem with saving the name.');
        }

        // set avatar color
        $partner->setAvatarColor();

        $partner->save();

        // this is really ugly. it should be changed
        if ($request->get('birthdate') == 'exact') {
            $birthdate = $request->input('birthdayDate');
            $birthdate = DateHelper::parseDate($birthdate);
            $day = $birthdate->day;
            $month = $birthdate->month;
            $year = $birthdate->year;
        } else {
            $day = $request->get('day');
            $month = $request->get('month');
            $year = $request->get('year');
        }

        app(UpdateBirthdayInformation::class)->execute([
            'account_id' => auth()->user()->account_id,
            'contact_id' => $partner->id,
            'is_date_known' => ! empty($request->get('birthdate')) && $request->get('birthdate') !== 'unknown',
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'is_age_based' => $request->get('birthdate') === 'approximate',
            'age' => $request->get('age'),
            'add_reminder' => ! empty($request->get('addReminder')),
        ]);

        // create the relationship
        $contact->setRelationship($partner, $request->get('relationship_type_id'));

        // check if the contact is partial
        if ($request->get('realContact')) {
            $partner->is_partial = false;
            $partner->save();
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.relationship_form_add_success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Contact $otherContact  significant other contact
     *
     * @return \Illuminate\View\View
     */
    public function edit(Contact $contact, Contact $otherContact)
    {
        $now = now();
        $age = (string) (! is_null($otherContact->birthdate) ? $otherContact->birthdate->getAge() : 0);
        $birthdate = ! is_null($otherContact->birthdate) ? $otherContact->birthdate->date->toDateString() : $now->toDateString();
        $day = ! is_null($otherContact->birthdate) ? $otherContact->birthdate->date->day : $now->day;
        $month = ! is_null($otherContact->birthdate) ? $otherContact->birthdate->date->month : $now->month;

        $hasBirthdayReminder = is_null($otherContact->birthday_reminder_id) ? 0 : 1;

        // Building the list of relationship types specifically for the dropdown which asks
        // for an id and a name.
        $arrayRelationshipTypes = collect();
        foreach (auth()->user()->account->relationshipTypes as $relationshipType) {
            $arrayRelationshipTypes->push([
                'id' => $relationshipType->id,
                'name' => $relationshipType->getLocalizedName($contact, true),
            ]);
        }

        // Get the nature of the current relationship
        $type = $contact->getRelationshipNatureWith($otherContact);

        return view('people.relationship.edit')
            ->withContact($contact)
            ->withPartner($otherContact)
            ->withDays(DateHelper::getListOfDays())
            ->withMonths(DateHelper::getListOfMonths())
            ->withBirthdayState($otherContact->getBirthdayState())
            ->withBirthdate($birthdate)
            ->withDay($day)
            ->withMonth($month)
            ->withAge($age)
            ->withGenders(auth()->user()->account->genders)
            ->withHasBirthdayReminder($hasBirthdayReminder)
            ->withRelationshipTypes($arrayRelationshipTypes)
            ->withType($type->relationship_type_id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Contact $otherContact  significant other contact
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Contact $contact, Contact $otherContact)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:50',
            'last_name' => 'max:100',
            'gender_id' => 'required|integer',
            'birthdayDate' => 'date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        // set the name of the contact
        if (! $otherContact->setName($request->input('first_name'), $request->input('last_name'))) {
            return back()
                ->withInput()
                ->withErrors('There has been a problem with saving the name.');
        }

        // set gender
        $otherContact->gender_id = $request->input('gender_id');
        $otherContact->save();

        // this is really ugly. it should be changed
        if ($request->get('birthdate') == 'exact') {
            $birthdate = $request->input('birthdayDate');
            $birthdate = DateHelper::parseDate($birthdate);
            $day = $birthdate->day;
            $month = $birthdate->month;
            $year = $birthdate->year;
        } else {
            $day = $request->get('day');
            $month = $request->get('month');
            $year = $request->get('year');
        }

        app(UpdateBirthdayInformation::class)->execute([
            'account_id' => auth()->user()->account_id,
            'contact_id' => $otherContact->id,
            'is_date_known' => ! empty($request->get('birthdate')) && $request->get('birthdate') !== 'unknown',
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'is_age_based' => $request->get('birthdate') === 'approximate',
            'age' => $request->get('age'),
            'add_reminder' => ! empty($request->get('addReminder')),
        ]);

        // update the relationship
        $contact->updateRelationship($otherContact, $request->get('type'), $request->get('relationship_type_id'));

        // check if the contact is partial
        if ($request->get('realContact')) {
            $otherContact->is_partial = false;
            $otherContact->save();
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.relationship_form_add_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Contact $otherContact
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Contact $contact, Contact $otherContact)
    {
        if ($contact->account_id != auth()->user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($otherContact->account_id != auth()->user()->account_id) {
            return redirect()->route('people.index');
        }

        $type = $contact->getRelationshipNatureWith($otherContact);
        $contact->deleteRelationship($otherContact, $type->relationship_type_id);

        // the contact is partial - if the relationship is deleted, the partial
        // contact has no reason to exist anymore
        if ($otherContact->is_partial) {
            $otherContact->deleteEverything();
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.relationship_form_deletion_success'));
    }
}
