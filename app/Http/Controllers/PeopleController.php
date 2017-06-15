<?php

namespace App\Http\Controllers;

use Auth;
use App\Note;
use Validator;
use App\Contact;
use Carbon\Carbon;
use App\Jobs\ResizeAvatars;
use Illuminate\Http\Request;

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
        
        $contacts = $user->account->contacts()->withCount('kids')->sortedBy($sort)->get();

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
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.profile', $data);
    }

    /**
     * Display the Edit people's view.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.edit', $data);
    }

    /**
     * Update the identity and address of the People object.
     * @param  Request $request
     * @param  int $peopleId
     * @return Response
     */
    public function update(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

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

        $contact->logEvent('contact', $contact->id, 'update');

        $request->session()->flash('success', trans('people.information_edit_success'));

        dispatch(new ResizeAvatars($contact));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Delete the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

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

        $request->session()->flash('success', trans('people.people_delete_success'));

        return redirect()->route('people.index');
    }

    /**
     * Show the Edit work view.
     * @param  Request $request
     * @return View
     */
    public function editWork(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.dashboard.work.edit', $data);
    }

    /**
     * Save the work information
     * @param int $id
     * @return
     */
    public function updateWork(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $job = $request->input('job');
        $company = $request->input('company');

        if ($job != '') {
            $contact->job = $job;
        } else {
            $contact->job = null;
        }

        if ($company != '') {
            $contact->company = $company;
        } else {
            $contact->company = null;
        }

        if ($request->input('linkedin') != '') {
            $contact->linkedin_profile_url = $request->input('linkedin');
        } else {
            $contact->linkedin_profile_url = null;
        }

        $contact->save();

        $request->session()->flash('success', trans('people.work_edit_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Show the Edit food preferencies view.
     * @param  Request $request
     * @param  [type]  $peopleId integer
     * @return View
     */
    public function editFoodPreferencies(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.dashboard.food-preferencies.edit', $data);
    }

    /**
     * Save the food preferencies.
     * @param int $id
     * @return
     */
    public function updateFoodPreferencies(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $food = $request->input('food');

        if ($food != '') {
            $contact->updateFoodPreferencies($food);
        } else {
            $contact->updateFoodPreferencies(null);
        }

        $request->session()->flash('success', trans('people.food_preferencies_add_success'));

        return redirect('/people/' . $contact->id);
    }
}
