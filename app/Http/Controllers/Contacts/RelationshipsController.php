<?php

namespace App\Http\Controllers\Contacts;

use App\Contact;
use App\Relationship;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\RelationshipsRequest;
use App\Http\Requests\People\ExistingRelationshipsRequest;

class RelationshipsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function index(Contact $contact)
    {
        return view('people.relationship.index')
            ->withContact($contact);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function create(Contact $contact)
    {
        return view('people.relationship.add')
            ->withContact($contact)
            ->withPartner(new Contact)
            ->withGenders(auth()->user()->account->genders);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param RelationshipsRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(RelationshipsRequest $request, Contact $contact)
    {
        // this is a real contact, not just a significant other
        if ($request->get('realContact')) {
            $partner = Contact::create(
                $request->only([
                    'first_name',
                    'last_name',
                    'gender_id',
                ])
                + [
                    'account_id' => $contact->account_id,
                ]
            );

            $partner->logEvent('contact', $partner->id, 'create');

            $contact->setRelationshipWith($partner, true);
        } else {
            $partner = Contact::create(
                $request->only([
                    'first_name',
                    'last_name',
                    'gender_id',
                ])
                + [
                    'account_id' => $contact->account_id,
                    'is_partial' => 1,
                ]
            );

            $contact->setRelationshipWith($partner);
        }

        $partner->setAvatarColor();

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
            ->with('success', trans('people.significant_other_add_success'));
    }

    /**
     * Store an existing contact as a significant other. When we add this kind of
     * relationship, we need to create two Relationship records, to match with
     * the bidirectional nature of the relationship.
     *
     * @param ExistingRelationshipsRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function storeExistingContact(ExistingRelationshipsRequest $request, Contact $contact)
    {
        $partner = Contact::findOrFail($request->get('existingPartner'));
        $contact->setRelationshipWith($partner, true);

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
