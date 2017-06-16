<?php

namespace App\Http\Controllers\People;

use App\Contact;
use App\Reminder;
use App\SignificantOther;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\SignificantOthersRequest;

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
            ->withSignificantOther(new SignificantOther);
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
        $significantOther = $contact->significantOthers()->create(
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

        $contact->logEvent('significantother', $significantOther->id, 'create');

        return redirect('/people/' . $contact->id)
            ->with('success', trans('people.significant_other_add_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param Contact $contact
     * @param SignificantOther $significantOther
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact, SignificantOther $significantOther)
    {
        //
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

        if($significantOther->reminder) {
            $significantOther->update([
                'birthday_reminder_id' => null
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

        return redirect('/people/' . $contact->id)
            ->with('success', trans('people.significant_other_edit_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param SignificantOther $significantOther
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, SignificantOther $significantOther)
    {
        if($significantOther->reminder) {
            $significantOther->reminder->delete();
        }

        $contact->events()->forObject($significantOther)->get()->each->delete();

        $significantOther->delete();

        return redirect('/people/' . $contact->id)
            ->with('success', trans('people.significant_other_delete_success'));
    }
}
