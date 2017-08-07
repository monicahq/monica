<?php

namespace App\Http\Controllers\People;

use App\Contact;
use App\Reminder;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\KidsRequest;
use App\Http\Requests\People\ExistingKidsRequest;

class KidsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function index(Contact $contact)
    {
        return view('people.dashboard.kids.index')
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
        return view('people.dashboard.kids.add')
            ->withContact($contact)
            ->withKid(new Contact)
            ->withKids($contact->getPossibleKids());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param KidsRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(KidsRequest $request, Contact $contact)
    {
        // this is a real contact, not just a significant other
        if ($request->get('realContact')) {
            $kid = Contact::create(
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

            $kid->logEvent('contact', $kid->id, 'create');

            $contact->setOffspring($kid, true);
        } else {
            $kid = Contact::create(
                $request->only([
                    'first_name',
                    'last_name',
                    'gender',
                    'is_birthdate_approximate',
                ])
                + [
                    'account_id' => $contact->account_id,
                    'is_kid' => 1,
                ]
            );

            $contact->setOffspring($kid);
        }

        $kid->setBirthday(
            $request->get('is_birthdate_approximate'),
            $request->get('birthdate'),
            $request->get('age')
        );

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.kids_add_success'));
    }

    /**
     * Store an existing contact as a kid. When we add this kind of
     * relationship, we need to create two Offspring records, to match with
     * the bidirectional nature of the relationship.
     *
     * @param ExistingKidsRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function storeExistingContact(ExistingKidsRequest $request, Contact $contact)
    {
        $kid = Contact::findOrFail($request->get('existingKid'));
        $contact->setOffspring($kid, true);

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_add_success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Contact $kid
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact, Contact $kid)
    {
        return view('people.dashboard.kids.edit')
            ->withContact($contact)
            ->withKid($kid);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param KidsRequest $request
     * @param Contact $contact
     * @param Kid $kid
     * @return \Illuminate\Http\Response
     */
    public function update(KidsRequest $request, Contact $contact, Contact $kid)
    {
        $kid->update(
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

        $kid->setBirthday(
            $request->get('is_birthdate_approximate'),
            $request->get('birthdate'),
            $request->get('age')
        );

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.kids_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Kid $kid
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Contact $kid)
    {
        if ($contact->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        if ($kid->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        if ($kid->reminders) {
            $kid->reminders()->get()->each->delete();
        }

        $contact->unsetKid($kid);

        $contact->deleteEventsWithKid($kid);

        $kid->delete();

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.kids_delete_success'));
    }

    /**
     * Unlink the relationship between those two people.
     *
     * @param  Contact $contact
     * @param  Contact $kid
     * @return
     */
    public function unlink(Contact $contact, Contact $kid)
    {
        if ($contact->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        if ($kid->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        $contact->unsetKid($kid, true);

        $contact->deleteEventsWithKid($kid);

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_delete_success'));
    }
}
