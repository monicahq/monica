<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Tag;
use Validator;
use App\Contact;
use App\ContactFieldType;
use App\Jobs\ResizeAvatars;
use App\Helpers\VCardHelper;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
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

        $date_flag = false;
        $date_sort = null;

        if (str_contains($sort, 'lastactivitydate')) {
            $date_sort = str_after($sort, 'lastactivitydate');
            $sort = 'firstnameAZ';
            $date_flag = true;
        }

        $tags = null;
        $url = '';
        $tagCount = 1;

        if ($request->get('tag1')) {
            $tags = Tag::where('name_slug', $request->get('tag1'))
                        ->where('account_id', auth()->user()->account_id)
                        ->get();

            $count = 2;

            while (true) {
                if ($request->get('tag'.$count)) {
                    $tags = $tags->concat(
                        Tag::where('name_slug', $request->get('tag'.$count))
                                    ->where('account_id', auth()->user()->account_id)
                                    ->get()
                    );
                } else {
                    break;
                }

                $count++;
            }
            if (is_null($tags)) {
                return redirect()->route('people.index');
            }

            $contacts = $user->account->contacts()->real()->sortedBy($sort);

            foreach ($tags as $tag) {
                $contacts = $contacts->whereHas('tags', function ($query) use ($tag) {
                    $query->where('id', $tag->id);
                });

                $url = $url.'tag'.$tagCount.'='.$tag->name_slug.'&';

                $tagCount++;
            }

            $contacts = $contacts->get();
        } else {
            $contacts = $user->account->contacts()->real()->sortedBy($sort)->get();
        }

        if ($date_flag) {
            foreach ($contacts as $contact) {
                $contact['sort_date'] = $contact->getLastActivityDate();
            }

            if ($date_sort == 'NewtoOld') {
                $contacts = $contacts->sortByDesc('sort_date');
            } elseif ($date_sort == 'OldtoNew') {
                $contacts = $contacts->sortBy('sort_date');
            }
        }

        return view('people.index')
            ->withContacts($contacts)
            ->withTags($tags)
            ->withUrl($url)
            ->withTagCount($tagCount);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = [
            'genders' => auth()->user()->account->genders,
        ];

        return view('people.create', $data);
    }

    public function missing()
    {
        return view('people.missing');
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
            'gender' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        $contact = new Contact;
        $contact->account_id = Auth::user()->account_id;
        $contact->gender_id = $request->input('gender');

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
        $age = (string) (! is_null($contact->birthdate) ? $contact->birthdate->getAge() : 0);
        $birthdate = ! is_null($contact->birthdate) ? $contact->birthdate->date->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d');
        $day = ! is_null($contact->birthdate) ? $contact->birthdate->date->day : \Carbon\Carbon::now()->day;
        $month = ! is_null($contact->birthdate) ? $contact->birthdate->date->month : \Carbon\Carbon::now()->month;

        return view('people.edit')
            ->withContact($contact)
            ->withDays(\App\Helpers\DateHelper::getListOfDays())
            ->withMonths(\App\Helpers\DateHelper::getListOfMonths())
            ->withBirthdayState($contact->getBirthdayState())
            ->withBirthdate($birthdate)
            ->withDay($day)
            ->withMonth($month)
            ->withAge($age)
            ->withGenders(auth()->user()->account->genders);
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
            'birthdate' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        if (! $contact->setName($request->input('firstname'), $request->input('lastname'))) {
            return back()
                ->withInput()
                ->withErrors('There has been a problem with saving the name.');
        }

        $contact->gender_id = $request->input('gender');

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
                    $specialDate->setReminder('year', 1, trans('people.deceased_reminder_title', ['name' => $contact->first_name]));
                }
            }
        }

        $contact->save();

        // Handling the case of the birthday
        $contact->removeSpecialDate('birthdate');
        switch ($request->input('birthdate')) {
            case 'approximate':
                $specialDate = $contact->setSpecialDateFromAge('birthdate', $request->input('age'));
                break;
            case 'almost':
                $specialDate = $contact->setSpecialDate(
                    'birthdate',
                    0,
                    $request->input('month'),
                    $request->input('day')
                );
                $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
                break;
            case 'exact':
                $birthdate = $request->input('birthdayDate');
                $birthdate = new \Carbon\Carbon($birthdate);
                $specialDate = $contact->setSpecialDate(
                    'birthdate',
                    $birthdate->year,
                    $birthdate->month,
                    $birthdate->day
                );
                $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
                break;
            case 'unknown':
            default:
                break;
        }

        $contact->logEvent('contact', $contact->id, 'update');

        dispatch(new ResizeAvatars($contact));

        $contact->updateGravatar();

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
        //
        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema="monica"');
        foreach ($tables as $table) {
            $tableName = $table->table_name;

            try {
                DB::table($tableName)->where('contact_id', $contact->id)->delete();
            } catch (QueryException $e) {
                continue;
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

        if (preg_match('/(.{1,})[:](.{1,})/', $needle, $matches)) {
            $search_field = $matches[1];
            $search_term = $matches[2];

            $field = ContactFieldType::where('name', 'LIKE', $search_field)->first();

            $field_id = $field->id;

            $results = Contact::whereHas('contactFields', function ($query) use ($field_id,$search_term) {
                $query->where([
                    ['data', 'like', "$search_term%"],
                    ['contact_field_type_id', $field_id],
                ]);
            })->get();
        } else {
            $results = Contact::search($needle, $accountId, 20, 'created_at');
        }

        if (count($results) !== 0) {
            return $results;
        } else {
            return ['noResults' => trans('people.people_search_no_results')];
        }
    }

    /**
     * Download the contact as vCard.
     * @param  Contact $contact
     * @return
     */
    public function vCard(Contact $contact)
    {
        if (config('app.debug')) {
            \Barryvdh\Debugbar\Facade::disable();
        }

        $vcard = VCardHelper::prepareVCard($contact);

        return  $vcard->download();
    }
}
