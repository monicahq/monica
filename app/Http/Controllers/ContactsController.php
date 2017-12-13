<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Tag;
use Validator;
use App\Contact;
use App\Jobs\ResizeAvatars;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContactsController extends Controller
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

        $tag = null;

        if ($request->get('tags')) {
            $tag = Tag::where('name_slug', $request->get('tags'))
                        ->where('account_id', auth()->user()->account_id)
                        ->first();

            if (is_null($tag)) {
                return redirect()->route('people.index');
            }

            $contacts = $user->account->contacts()->real()->whereHas('tags', function ($query) use ($tag) {
                $query->where('id', $tag->id);
            })->sortedBy($sort)->get();
        } else {
            $contacts = $user->account->contacts()->real()->sortedBy($sort)->get();
        }

        return view('people.index')
            ->withContacts($contacts)
            ->withTag($tag);
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
            'first_name' => 'required|max:50',
            'last_name' => 'nullable|max:100',
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

        $contact->first_name = $request->input('first_name');
        $contact->last_name = $request->input('last_name', null);

        $contact->save();

        $contact->setAvatarColor();

        $contact->logEvent('contact', $contact->id, 'create');

        // Did the user press "Save" or "Submit and add another person"
        if (! is_null($request->get('save'))) {
            return redirect()->route('people.show', ['id' => $contact->id]);
        } else {
            return redirect()->route('people.create')
                            ->with('status', trans('people.people_add_success', ['name' => $contact->getCompleteName(auth()->user()->name_order)]));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact)
    {
        // make sure we don't display a significant other if it's not set as a
        // real contact
        if ($contact->is_partial) {
            return redirect('/people');
        }

        $contact->load(['notes' => function ($query) {
            $query->orderBy('updated_at', 'desc');
        }]);

        $reminders = $contact->getRemindersAboutRelatives();

        $contact->last_consulted_at = \Carbon\Carbon::now(auth()->user()->timezone);
        $contact->save();

        return view('people.profile')
            ->withContact($contact)
            ->withReminders($reminders);
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
            'firstname' => 'required|max:50',
            'lastname' => 'max:100',
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
        $contact->last_name = $request->input('lastname');

        if ($request->file('avatar') != '') {
            $contact->has_avatar = true;
            $contact->avatar_location = config('filesystems.default');
            $contact->avatar_file_name = $request->avatar->store('avatars', config('filesystems.default'));
        }

        // Is the person deceased?
        $contact->removeSpecialDate('deceased_date');
        $contact->is_dead = false;

        if ($request->input('markPersonDeceased') != '') {
            $contact->is_dead = true;

            if ($request->input('checkboxDatePersonDeceased') != '') {
                $specialDate = $contact->setSpecialDate('deceased_date', $request->input('deceased_date_year'), $request->input('deceased_date_month'), $request->input('deceased_date_day'));

                if ($request->input('addReminderDeceased') != '') {
                    $newReminder = $specialDate->setReminder('year', 1, trans('people.deceased_reminder_title', ['name' => $contact->first_name]));
                }
            }
        }

        $contact->save();

        // Saves birthdate if defined
        $contact->removeSpecialDate('birthdate');
        switch ($request->input('birthdate')) {
            case 'unknown':
                break;
            case 'approximate':
                $specialDate = $contact->setSpecialDateFromAge('birthdate', $request->input('age'));
                break;
            case 'exact':
                $specialDate = $contact->setSpecialDate('birthdate', $request->input('birthdate_year'), $request->input('birthdate_month'), $request->input('birthdate_day'));
                $newReminder = $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
                break;
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

        return redirect('/people/'.$contact->id)
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
        // I know: this is a really brutal way of deleting objects. I'm doing
        // this because I'll add more objects related to contacts in the future
        // and I don't want to have to think of deleting a row that matches a
        // contact.
        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema="monica"');
        foreach ($tables as $table) {
            $tableName = $table->table_name;
            $tableData = DB::table($tableName)->get();

            $contactIdRowExists = false;
            foreach ($tableData as $data) {
                foreach ($data as $columnName => $value) {
                    if ($columnName == 'contact_id') {
                        $contactIdRowExists = true;
                    }
                }
            }

            if ($contactIdRowExists == true) {
                DB::table($tableName)->where('contact_id', $contact->id)->delete();
            }
        }

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
     * Save the work information.
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

        return redirect('/people/'.$contact->id)
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

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.food_preferencies_add_success'));
    }

    /**
     * Search used in the header.
     * @param  Request $request
     */
    public function search(Request $request)
    {
        $needle = $request->needle;
        $accountId = $request->accountId;

        if ($accountId != auth()->user()->account_id) {
            return;
        }

        if ($needle == null) {
            return;
        }

        if ($accountId == null) {
            return;
        }

        $results = Contact::search($needle, $accountId);
        if (count($results) !== 0) {
            return $results;
        } else {
            return ['noResults' => trans('people.people_search_no_results')];
        }
    }
}
