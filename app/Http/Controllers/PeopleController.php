<?php

namespace App\Http\Controllers;

use Auth;
use App\Note;
use Validator;
use App\Contact;
use App\Reminder;
use Carbon\Carbon;
use App\Jobs\ResizeAvatars;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PeopleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $sort = $request->get('sort') ?? $user->contacts_sort_order;

        if ($user->contacts_sort_order !== $sort) {
            $user->updateContactViewPreference($sort);
        }

        $contacts = $user->account->contacts()->sortedBy($sort)->get();

        return view('people.index')
            ->withContacts($contacts);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('people.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:255',
            'gender' => 'required',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        $contact = new Contact;
        $contact->account_id = Auth::user()->account_id;
        $contact->gender = $request->input('gender');
        $contact->first_name = ucfirst($request->input('first_name'));

        if (!empty($request->input('last_name'))) {
            $contact->last_name = ucfirst($request->input('last_name'));
        }

        $contact->is_birthdate_approximate = 'unknown';
        $contact->save();

        $contact->setAvatarColor();

        $contact->logEvent('contact', $contact->id, 'create');

        return redirect()->route('people.show', ['id' => $contact->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact)
    {
        $contact->load(['notes' => function ($query) {
            $query->orderBy('updated_at', 'desc');
        }]);

        return view('people.profile')
            ->withContact($contact);
    }

    /**
     * Display the Edit people's view.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact)
    {

        return view('people.edit')
            ->withContact($contact);
    }

    /**
     * Update the identity and address of the People object.
     *
     * @param  Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contact $contact)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|max:255',
            'gender' => 'required',
            'file' => 'max:10240',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        $contact->gender = $request->input('gender');
        $contact->first_name = $request->input('firstname');

        if ($request->input('lastname') != '') {
            $contact->last_name = $request->input('lastname');
        } else {
            $contact->last_name = null;
        }

        if ($request->file('avatar') != '') {
            $contact->has_avatar = 'true';
            $contact->avatar_file_name = $request->file('avatar')->store('avatars', 'public');
        }

        if ($request->input('email') != '') {
            $contact->email = $request->input('email');
        } else {
            $contact->email = null;
        }

        if ($request->input('phone') != '') {
            $contact->phone_number = $request->input('phone');
        } else {
            $contact->phone_number = null;
        }

        if ($request->input('facebook') != '') {
            $contact->facebook_profile_url = $request->input('facebook');
        } else {
            $contact->facebook_profile_url = null;
        }

        if ($request->input('twitter') != '') {
            $contact->twitter_profile_url = $request->input('twitter');
        } else {
            $contact->twitter_profile_url = null;
        }

        if ($request->input('street') != '') {
            $contact->street = $request->input('street');
        } else {
            $contact->street = null;
        }

        if ($request->input('postalcode') != '') {
            $contact->postal_code = $request->input('postalcode');
        } else {
            $contact->postal_code = null;
        }

        if ($request->input('province') != '') {
            $contact->province = $request->input('province');
        } else {
            $contact->province = null;
        }

        if ($request->input('city') != '') {
            $contact->city = $request->input('city');
        } else {
            $contact->city = null;
        }

        if ($request->input('country') != '---') {
            $contact->country_id = $request->input('country');
        } else {
            $contact->country_id = null;
        }

        $birthdateApproximate = $request->input('birthdateApproximate');

        if ($birthdateApproximate == 'approximate') {
            $age = $request->input('age');
            $year = Carbon::now()->subYears($age)->year;
            $birthdate = Carbon::createFromDate($year, 1, 1);
            $contact->birthdate = $birthdate;
        } elseif ($birthdateApproximate == 'unknown') {
            $contact->birthdate = null;
        } else {
            $birthdate = Carbon::createFromFormat('Y-m-d', $request->input('specificDate'));
            $contact->birthdate = $birthdate;
        }

        $contact->is_birthdate_approximate = $birthdateApproximate;
        $contact->save();

        if ($birthdateApproximate == 'exact') {

            // check if a reminder was previously set for this birthdate
            // if so, we delete the old reminder, and create a new one
            if (! is_null($contact->birthday_reminder_id)) {
                $contact->reminders->find($contact->birthday_reminder_id)->delete();
            }

            $reminder = Reminder::addBirthdayReminder(
                $contact,
                trans(
                    'people.people_add_birthday_reminder',
                    ['name' => $request->get('firstname')]
                ),
                $request->get('specificDate')
            );

            $contact->update([
                'birthday_reminder_id' => $reminder->id,
            ]);
        } else {

            // the birthdate is approximate or unknown. in both cases, we need
            // to remove the previous reminder about the birthday if there was
            // an existing one
            if (! is_null($contact->birthday_reminder_id)) {
                $contact->reminders->find($contact->birthday_reminder_id)->delete();

                $contact->update([
                    'birthday_reminder_id' => null,
                ]);
            }
        }

        $contact->logEvent('contact', $contact->id, 'update');

        dispatch(new ResizeAvatars($contact));

        // for performance reasons, we check if a gravatar exists for this email
        // address. if it does, we store the gravatar url in the database.
        // while this is not ideal because the gravatar can change, at least we
        // won't make constant call to gravatar to load the avatar on every
        // page load.
        $response = $contact->getGravatar(250);
        if ($response != false and is_string($response)) {
            $contact->gravatar_url = $response;
            $contact->save();
        } else {
            $contact->gravatar_url = null;
            $contact->save();
        }

        return redirect('/people/' . $contact->id)
            ->with('success', trans('people.information_edit_success'));
    }

    /**
     * Delete the specified resource.
     *
     * @param Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, Contact $contact)
    {
        $contact->activities->each->delete();
        $contact->debts->each->delete();
        $contact->events->each->delete();
        $contact->gifts->each->delete();
        $contact->kids->each->delete();
        $contact->notes->each->delete();
        $contact->reminders->each->delete();
        $contact->significantOthers->each->delete();
        $contact->tasks->each->delete();

        $contact->delete();

        return redirect()->route('people.index')
            ->with('success', trans('people.people_delete_success'));
    }

    /**
     * Show the Edit work view.
     *
     * @param  Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function editWork(Request $request, Contact $contact)
    {
        return view('people.dashboard.work.edit')
            ->withContact($contact);
    }

    /**
     * Save the work information
     *
     * @param Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function updateWork(Request $request, Contact $contact)
    {
        $job = $request->input('job');
        $company = $request->input('company');
        $linkedin = $request->input('linkedin');

        $contact->job = ! empty($job) ? $job : null;
        $contact->company = ! empty($company) ? $company : null;
        $contact->linkedin_profile_url = ! empty($linkedin) ? $linkedin : null;

        $contact->save();

        return redirect('/people/' . $contact->id)
            ->with('success', trans('people.work_edit_success'));
    }

    /**
     * Show the Edit food preferencies view.
     *
     * @param  Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function editFoodPreferencies(Request $request, Contact $contact)
    {
        return view('people.dashboard.food-preferencies.edit')
            ->withContact($contact);
    }

    /**
     * Save the food preferencies.
     *
     * @param Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function updateFoodPreferencies(Request $request, Contact $contact)
    {
        $food = ! empty($request->get('food')) ? $request->get('food') : null;

        $contact->updateFoodPreferencies($food);

        return redirect('/people/' . $contact->id)
            ->with('success', trans('people.food_preferencies_add_success'));
    }
}
