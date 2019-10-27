<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Helpers\GendersHelper;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Relationship\Relationship;
use Illuminate\Support\Facades\Validator;
use App\Services\Contact\Contact\CreateContact;
use App\Services\Contact\Contact\UpdateContact;
use App\Services\Contact\Relationship\CreateRelationship;
use App\Services\Contact\Relationship\UpdateRelationship;
use App\Services\Contact\Relationship\DestroyRelationship;
use App\Http\Resources\Contact\ContactShort as ContactResource;

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
        $existingContacts = Contact::search('', Auth::user()->account_id, 20, 'updated_at', 'AND `id` != '.$contact->id);

        return view('people.relationship.new')
            ->withContact($contact)
            ->withPartner(new Contact)
            ->withGenders(GendersHelper::getGendersInput())
            ->withRelationshipTypes($this->getRelationshipTypesList($contact))
            ->withDefaultGender(auth()->user()->account->default_gender_id)
            ->withDays(DateHelper::getListOfDays())
            ->withMonths(DateHelper::getListOfMonths())
            ->withBirthdate(now(DateHelper::getTimezone())->toDateString())
            ->withExistingContacts(ContactResource::collection($existingContacts))
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
            $partnerId = $request->get('existing_contact_id');
        } else {

            // case of creating a new contact
            $datas = $this->validateAndGetDatas($request);

            if ($datas instanceof \Illuminate\Contracts\Validation\Validator) {
                return back()
                    ->withInput()
                    ->withErrors($datas);
            }

            $partner = app(CreateContact::class)->execute($datas);
            $partnerId = $partner->id;
        }

        app(CreateRelationship::class)->execute([
            'account_id' => auth()->user()->account_id,
            'contact_is' => $contact->id,
            'of_contact' => $partnerId,
            'relationship_type_id' => $request->get('relationship_type_id'),
        ]);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.relationship_form_add_success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Relationship $relationship
     *
     * @return \Illuminate\View\View
     */
    public function edit(Contact $contact, Relationship $relationship)
    {
        $otherContact = $relationship->ofContact;

        $now = now();
        $age = (string) (! is_null($otherContact->birthdate) ? $otherContact->birthdate->getAge() : 0);
        $birthdate = ! is_null($otherContact->birthdate) ? $otherContact->birthdate->date->toDateString() : $now->toDateString();
        $day = ! is_null($otherContact->birthdate) ? $otherContact->birthdate->date->day : $now->day;
        $month = ! is_null($otherContact->birthdate) ? $otherContact->birthdate->date->month : $now->month;

        $hasBirthdayReminder = is_null($otherContact->birthday_reminder_id) ? 0 : 1;

        return view('people.relationship.edit')
            ->withContact($contact)
            ->withPartner($otherContact)
            ->withGenders(auth()->user()->account->genders)
            ->withRelationshipTypes($this->getRelationshipTypesList($contact))
            ->withDays(DateHelper::getListOfDays())
            ->withMonths(DateHelper::getListOfMonths())
            ->withBirthdate($birthdate)
            ->withRelationshipId($relationship->id)
            ->withType($relationship->relationship_type_id)
            ->withBirthdayState($otherContact->getBirthdayState())
            ->withDay($day)
            ->withMonth($month)
            ->withAge($age)
            ->withGenders(GendersHelper::getGendersInput())
            ->withHasBirthdayReminder($hasBirthdayReminder);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Relationship $relationship
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Contact $contact, Relationship $relationship)
    {
        $otherContact = $relationship->ofContact;

        if ($otherContact->is_partial) {
            $datas = $this->validateAndGetDatas($request);

            if ($datas instanceof \Illuminate\Contracts\Validation\Validator) {
                return back()
                    ->withInput()
                    ->withErrors($datas);
            }

            app(UpdateContact::class)->execute($datas + [
                'contact_id' => $otherContact->id,
            ]);
        }

        // update the relationship
        app(UpdateRelationship::class)->execute([
            'account_id' => auth()->user()->account_id,
            'relationship_id' => $relationship->id,
            'relationship_type_id' => $request->get('relationship_type_id'),
        ]);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.relationship_form_add_success'));
    }

    /**
     * Validate datas and get an array for create or update a contact.
     *
     * @param Request $request
     * @return array|\Illuminate\Contracts\Validation\Validator
     */
    private function validateAndGetDatas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:255',
            'last_name' => 'max:255',
            'gender_id' => 'nullable|integer',
            'birthdayDate' => 'date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $validator;
        }

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

        return [
            'account_id' => auth()->user()->account_id,
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'gender_id' => $request->input('gender_id'),
            'is_birthdate_known' => ! empty($request->get('birthdate')) && $request->get('birthdate') !== 'unknown',
            'birthdate_day' => $day,
            'birthdate_month' => $month,
            'birthdate_year' => $year,
            'birthdate_is_age_based' => $request->get('birthdate') === 'approximate',
            'birthdate_age' => $request->get('age'),
            'birthdate_add_reminder' => ! empty($request->get('addReminder')),
            'is_partial' => ! $request->get('realContact'),
            'is_deceased' => false,
            'is_deceased_date_known' => false,
        ];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Relationship $relationship
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Contact $contact, Relationship $relationship)
    {
        if ($contact->account_id != auth()->user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($relationship->account_id != auth()->user()->account_id) {
            return redirect()->route('people.index');
        }

        app(DestroyRelationship::class)->execute([
            'account_id' => auth()->user()->account_id,
            'relationship_id' => $relationship->id,
        ]);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.relationship_form_deletion_success'));
    }

    /**
     * Building the list of relationship types specifically for the dropdown which asks
     * for an id and a name.
     *
     * @return Collection
     */
    private function getRelationshipTypesList(Contact $contact)
    {
        $relationshipTypes = collect();
        foreach (auth()->user()->account->relationshipTypes as $relationshipType) {
            $types = $relationshipTypes->get($relationshipType->relationshipTypeGroup->name, [
                'name' => trans('app.relationship_type_group_'.$relationshipType->relationshipTypeGroup->name),
                'options' => [],
            ]);

            $types['options'][] = [
                'id' => $relationshipType->id,
                'name' => $relationshipType->getLocalizedName($contact, true),
            ];

            $relationshipTypes->put($relationshipType->relationshipTypeGroup->name, $types);
        }

        return $relationshipTypes;
    }
}
