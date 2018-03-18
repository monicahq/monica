<?php

namespace App\Http\Controllers\Contacts;

use App\Contact;
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
            ->withGenders(auth()->user()->account->genders);
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
                    'gender_id',
                ])
                + [
                    'account_id' => $contact->account_id,
                ]
            );

            $kid->logEvent('contact', $kid->id, 'create');

            $kid->isTheOffspringOf($contact, true);
        } else {
            $kid = Contact::create(
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

            $kid->isTheOffspringOf($contact);
        }

        $kid->setAvatarColor();

        // birthdate
        $kid->removeSpecialDate('birthdate');
        switch ($request->input('birthdate')) {
            case 'approximate':
                $specialDate = $kid->setSpecialDateFromAge('birthdate', $request->input('age'));
                break;
            case 'exact':
                $specialDate = $kid->setSpecialDate('birthdate', $request->input('birthdate_year'), $request->input('birthdate_month'), $request->input('birthdate_day'));
                $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $kid->first_name]));
                break;
            case 'unknown':
            default:
                break;
        }

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
        $kid->isTheOffspringOf($contact, true);

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
            ->withKid($kid)
            ->withGenders(auth()->user()->account->genders);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param KidsRequest $request
     * @param Contact $contact
     * @param Contact $kid
     * @return \Illuminate\Http\Response
     */
    public function update(KidsRequest $request, Contact $contact, Contact $kid)
    {
        $kid->update(
            $request->only([
                'first_name',
                'last_name',
                'gender_id',
            ])
            + [
                'account_id' => $contact->account_id,
            ]
        );

        // birthdate
        $kid->removeSpecialDate('birthdate');
        switch ($request->input('birthdate')) {
            case 'approximate':
                $specialDate = $kid->setSpecialDateFromAge('birthdate', $request->input('age'));
                break;
            case 'exact':
                $specialDate = $kid->setSpecialDate('birthdate', $request->input('birthdate_year'), $request->input('birthdate_month'), $request->input('birthdate_day'));
                $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $kid->first_name]));
                break;
            case 'unknown':
            default:
                break;
        }

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.kids_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Contact $kid
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

        $contact->unsetOffspring($kid);

        $kid->specialDates->each->delete();
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

        $contact->unsetOffspring($kid, true);

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_delete_success'));
    }
}
