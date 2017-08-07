<?php

namespace App\Http\Controllers\People;

use App\Contact;
use App\Reminder;
use App\Relationship;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\SignificantOthersRequest;
use App\Http\Requests\People\ExistingSignificantOthersRequest;

class SignificantOthersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function index(Contact $contact)
    {
        return view('people.dashboard.significantother.index')
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
        return view('people.dashboard.significantother.add')
            ->withContact($contact)
            ->withPartner(new Contact)
            ->withPartners($contact->getPossiblePartners());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param SignificantOthersRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(SignificantOthersRequest $request, Contact $contact)
    {
        // this is a real contact, not just a significant other
        if ($request->get('realContact')) {
            $partner = Contact::create(
                $request->only([
                    'first_name',
                    'last_name',
                    'gender',
                    'is_birthdate_approximate',
                ])
                + [
                    'account_id' => $contact->account_id
                ]
            );

            $partner->logEvent('contact', $partner->id, 'create');

            $contact->setPartner($partner, true);
        } else {
            $partner = Contact::create(
                $request->only([
                    'first_name',
                    'last_name',
                    'gender',
                    'is_birthdate_approximate',
                ])
                + [
                    'account_id' => $contact->account_id,
                    'is_significant_other' => 1,
                ]
            );

            $contact->setPartner($partner);
        }

        $partner->setBirthday(
            $request->get('is_birthdate_approximate'),
            $request->get('birthdate'),
            $request->get('age')
        );

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_add_success'));
    }

    /**
     * Store an existing contact as a significant other. When we add this kind of
     * relationship, we need to create two Relationship records, to match with
     * the bidirectional nature of the relationship.
     *
     * @param ExistingSignificantOthersRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function storeExistingContact(ExistingSignificantOthersRequest $request, Contact $contact)
    {
        $partner = Contact::findOrFail($request->get('existingPartner'));
        $contact->setPartner($partner, true);

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
        return view('people.dashboard.significantother.edit')
            ->withContact($contact)
            ->withPartner($partner);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param SignificantOthersRequest $request
     * @param Contact $contact
     * @param SignificantOther $significantOther
     * @return \Illuminate\Http\Response
     */
    public function update(SignificantOthersRequest $request, Contact $contact, Contact $partner)
    {
        $partner->update(
            $request->only([
                'first_name',
                'last_name',
                'gender',
                'is_birthdate_approximate',
            ])
            + [
                'account_id' => $contact->account_id,
            ]
        );

        $partner->setBirthday(
            $request->get('is_birthdate_approximate'),
            $request->get('birthdate'),
            $request->get('age')
        );

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

        $contact->unsetPartner($partner);

        $contact->deleteEventsWithPartner($partner);

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

        $contact->unsetPartner($partner, true);

        $contact->deleteEventsWithPartner($partner);

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_delete_success'));
    }
}
