<?php

namespace App\Http\Controllers\People;

use App\Contact;
use App\Http\Requests\People\KidsRequest;
use App\Kid;
use App\Reminder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
            ->withKid(new Kid);
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
        $kid = $contact->kids()->create(
            $request->only([
                'first_name',
                'gender',
                'is_birthdate_approximate',
            ])
            + [
                'account_id' => $contact->account_id,
                'food_preferencies' => $request->get('food_preferences')
            ]
        );

        $kid->assignBirthday(
            $request->get('is_birthdate_approximate'),
            $request->get('birthdate'),
            $request->get('age')
        );

        if ($kid->is_birthdate_approximate === 'exact') {
            $reminder = Reminder::addBirthdayReminder(
                $contact,
                trans(
                    'people.kids_add_birthday_reminder',
                    ['name' => $request->get('first_name'), 'contact_firstname' => $contact->first_name]
                ),
                $request->get('birthdate'),
                $kid
            );

            $kid->update([
                'birthday_reminder_id' => $reminder->id,
            ]);
        }

        $contact->logEvent('kid', $kid->id, 'create');

        return redirect('/people/' . $contact->id)
            ->with('success', trans('people.kids_add_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param Contact $contact
     * @param Kid $kid
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact, Kid $kid)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Kid $kid
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact, Kid $kid)
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
    public function update(KidsRequest $request, Contact $contact, Kid $kid)
    {
        $kid->update(
            $request->only([
                'first_name',
                'gender',
                'is_birthdate_approximate',
            ])
            + [
                'account_id' => $contact->account_id,
                'food_preferencies' => $request->get('food_preferences')
            ]
        );

        $kid->assignBirthday(
            $request->get('is_birthdate_approximate'),
            $request->get('birthdate'),
            $request->get('age')
        );

        if ($kid->reminder) {
            $kid->update([
                'birthday_reminder_id' => null,
            ]);

            $kid->reminder->delete();
        }

        $kid->refresh();

        if ($kid->is_birthdate_approximate === 'exact') {
            $reminder = Reminder::addBirthdayReminder(
                $contact,
                trans(
                    'people.kids_add_birthday_reminder',
                    ['name' => $request->get('first_name'), 'contact_firstname' => $contact->first_name]
                ),
                $request->get('birthdate'),
                $kid
            );

            $kid->update([
                'birthday_reminder_id' => $reminder->id,
            ]);
        }

        $contact->logEvent('kid', $kid->id, 'update');

        return redirect('/people/' . $contact->id)
            ->with('success', trans('people.kids_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Kid $kid
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Kid $kid)
    {
        if ($kid->reminder) {
            $kid->reminder->delete();
        }

        $contact->events()->forObject($kid)->get()->each->delete();

        $kid->delete();

        return redirect('/people/' . $contact->id)
            ->with('success', trans('people.kids_delete_success'));
    }
}
