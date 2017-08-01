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
        $significantOther = Contact::create(
            $request->only([
                'first_name',
                'gender',
                'is_birthdate_approximate',
            ])
            + [
                'account_id' => $contact->account_id,
                'is_significant_other' => 1,
            ]
        );

        $relationship = Relationship::create(
            [
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'with_contact_id' => $significantOther->id,
                'is_active' => 1,
            ]
        );

        $significantOther->assignBirthday(
            $request->get('is_birthdate_approximate'),
            $request->get('birthdate'),
            $request->get('age')
        );

        if ($significantOther->is_birthdate_approximate === 'exact') {
            $reminder = Reminder::addBirthdayReminder(
                $significantOther,
                trans(
                    'people.significant_other_add_birthday_reminder',
                    ['name' => $request->get('first_name'), 'contact_firstname' => $contact->first_name]
                ),
                $request->get('birthdate')
            );
        }

        $contact->logEvent('significantother', $significantOther->id, 'create');

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_add_success'));
    }

    /**
     * Store an existing contact as a significant other. When we add this kind of
     * relationship, we need to create two Relationship records, to match with
     * the bidirectional nature of the relationship.
     *
     * @param SignificantOthersRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function storeExistingContact(ExistingSignificantOthersRequest $request, Contact $contact)
    {
        $relationship = Relationship::create(
            [
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'with_contact_id' => $request->get('existingPartner'),
                'is_active' => 1,
            ]
        );

        $relationship = Relationship::create(
            [
                'account_id' => $contact->account_id,
                'contact_id' => $request->get('existingPartner'),
                'with_contact_id' => $contact->id,
                'is_active' => 1,
            ]
        );

        $contact->logEvent('partner', $request->get('existingPartner'), 'create');

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
    public function edit(Contact $contact, SignificantOther $significantOther)
    {
        return view('people.dashboard.significantother.edit')
            ->withContact($contact)
            ->withSignificantOther($significantOther);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param SignificantOthersRequest $request
     * @param Contact $contact
     * @param SignificantOther $significantOther
     * @return \Illuminate\Http\Response
     */
    public function update(SignificantOthersRequest $request, Contact $contact, SignificantOther $significantOther)
    {
        $significantOther->update(
            $request->only([
                'first_name',
                'gender',
                'is_birthdate_approximate',
            ])
            + [
                'account_id' => $contact->account_id,
                'status' => 'active',
            ]
        );

        $significantOther->assignBirthday(
            $request->get('is_birthdate_approximate'),
            $request->get('birthdate'),
            $request->get('age')
        );

        if ($significantOther->reminder) {
            $significantOther->update([
                'birthday_reminder_id' => null,
            ]);

            $significantOther->reminder->delete();
        }

        $significantOther->refresh();

        if ($significantOther->is_birthdate_approximate === 'exact') {
            $reminder = Reminder::addBirthdayReminder(
                $contact,
                trans(
                    'people.significant_other_add_birthday_reminder',
                    ['name' => $request->get('first_name'), 'contact_firstname' => $contact->first_name]
                ),
                $request->get('birthdate'),
                null,
                $significantOther
            );

            $significantOther->update([
                'birthday_reminder_id' => $reminder->id,
            ]);
        }

        $contact->logEvent('significantother', $significantOther->id, 'update');

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
    public function destroy(Contact $contact, Contact $significantOther)
    {
        if ($contact->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        if ($significantOther->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        if ($significantOther->reminders) {
            $significantOther->reminders()->get()->each->delete();
        }

        $relationship = Relationship::where('contact_id', $contact->id)
                        ->where('with_contact_id', $significantOther->id)
                        ->first();

        $relationship->delete();

        $contact->events()->forObject($significantOther)->get()->each->delete();

        $significantOther->delete();

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_delete_success'));
    }

    /**
     * Unlink the relationship between those two people.
     *
     * @param  Contact $contact          [description]
     * @param  Contact $significantOther [description]
     * @return
     */
    public function unlink(Contact $contact, Contact $significantOther)
    {
        if ($contact->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        if ($significantOther->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        $relationship = Relationship::where('contact_id', $contact->id)
                        ->where('with_contact_id', $significantOther->id)
                        ->first();

        $relationship->delete();

        $relationship = Relationship::where('contact_id', $significantOther->id)
                        ->where('with_contact_id', $contact->id)
                        ->first();

        $relationship->delete();

        $contact->events()->forObject($significantOther)->get()->each->delete();

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.significant_other_delete_success'));
    }
}
