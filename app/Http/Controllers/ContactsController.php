<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use App\Jobs\ResizeAvatars;
use App\Models\Contact\Tag;
use App\Helpers\VCardHelper;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Auth;
use App\Models\Contact\ContactFieldType;
use App\Models\Relationship\Relationship;
use Barryvdh\Debugbar\Facade as Debugbar;
use Illuminate\Support\Facades\Validator;

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

        $tags = null;
        $url = '';
        $count = 1;

        if ($request->get('no_tag')) {
            //get tag less contacts
            $contacts = $user->account->contacts()->real()->sortedBy($sort);
            $contacts = $contacts->tags('NONE')->get();
        } elseif ($request->get('tag1')) {
            // get contacts with selected tags

            $tags = collect();

            while ($request->get('tag'.$count)) {
                $tag = Tag::where('name_slug', $request->get('tag'.$count))
                            ->where('account_id', auth()->user()->account_id)
                            ->get();

                if (! ($tags->contains($tag[0]))) {
                    $tags = $tags->concat($tag);
                }

                $url = $url.'tag'.$count.'='.$tag[0]->name_slug.'&';

                $count++;
            }
            if (is_null($tags)) {
                return redirect()->route('people.index');
            }

            $contacts = $user->account->contacts()->real()->sortedBy($sort);

            $contacts = $contacts->tags($tags)->get();
        } else {
            // get all contacts
            $contacts = $user->account->contacts()->real()->sortedBy($sort)->get();
        }

        return view('people.index')
            ->withContacts($contacts->unique('id'))
            ->withTags($tags)
            ->withUserTags(auth()->user()->account->tags)
            ->withUrl($url)
            ->withTagCount($count)
            ->withTagLess($request->get('no_tag') ?? false);
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
            'nickname' => 'nullable|max:100',
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
        $contact->nickname = $request->input('nickname', null);

        $contact->setAvatarColor();
        $contact->save();

        $contact->logEvent('contact', $contact->id, 'create');

        // Did the user press "Save" or "Submit and add another person"
        if (! is_null($request->get('save'))) {
            return redirect()->route('people.show', ['id' => $contact->hashID()]);
        } else {
            return redirect()->route('people.create')
                            ->with('status', trans('people.people_add_success', ['name' => $contact->name]));
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

        $contact->last_consulted_at = Carbon::now(auth()->user()->timezone);
        $contact->save();

        $relationships = $contact->relationships;

        // get love relationship type
        $loveRelationships = $relationships->filter(function ($item) {
            return $item->relationshipType->relationshipTypeGroup->name == 'love';
        });

        // get family relationship type
        $familyRelationships = $relationships->filter(function ($item) {
            return $item->relationshipType->relationshipTypeGroup->name == 'family';
        });

        // get friend relationship type
        $friendRelationships = $relationships->filter(function ($item) {
            return $item->relationshipType->relationshipTypeGroup->name == 'friend';
        });

        // get work relationship type
        $workRelationships = $relationships->filter(function ($item) {
            return $item->relationshipType->relationshipTypeGroup->name == 'work';
        });

        // reminders
        $reminders = $contact->reminders;
        $relevantRemindersFromRelatedContacts = $contact->getBirthdayRemindersAboutRelatedContacts();
        $reminders = $reminders->merge($relevantRemindersFromRelatedContacts)
                                ->sortBy('next_expected_date');

        // list of active features
        $modules = $contact->account->modules()->active()->get();

        return view('people.profile')
            ->withLoveRelationships($loveRelationships)
            ->withFamilyRelationships($familyRelationships)
            ->withFriendRelationships($friendRelationships)
            ->withWorkRelationships($workRelationships)
            ->withReminders($reminders)
            ->withModules($modules)
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
        $age = (string) (! is_null($contact->birthdate) ? $contact->birthdate->getAge() : 0);
        $birthdate = ! is_null($contact->birthdate) ? $contact->birthdate->date->format('Y-m-d') : now()->format('Y-m-d');
        $day = ! is_null($contact->birthdate) ? $contact->birthdate->date->day : now()->day;
        $month = ! is_null($contact->birthdate) ? $contact->birthdate->date->month : now()->month;

        $hasBirthdayReminder = ! is_null($contact->birthdate) ? (is_null($contact->birthdate->reminder) ? 0 : 1) : 0;

        return view('people.edit')
            ->withContact($contact)
            ->withDays(DateHelper::getListOfDays())
            ->withMonths(DateHelper::getListOfMonths())
            ->withBirthdayState($contact->getBirthdayState())
            ->withBirthdate($birthdate)
            ->withDay($day)
            ->withMonth($month)
            ->withAge($age)
            ->withHasBirthdayReminder($hasBirthdayReminder)
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
            'nickname' => 'max:100',
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
        $contact->nickname = $request->input('nickname', null);

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

        // Handling the case of the birthday
        $contact->removeSpecialDate('birthdate');
        switch ($request->input('birthdate')) {
            case 'unknown':
                break;
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

                if ($request->input('addReminder') != '') {
                    $newReminder = $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
                }

                break;
            case 'exact':
                $birthdate = $request->input('birthdayDate');
                $birthdate = new Carbon($birthdate);
                $specialDate = $contact->setSpecialDate(
                    'birthdate',
                    $birthdate->year,
                    $birthdate->month,
                    $birthdate->day
                );

                if ($request->input('addReminder') != '') {
                    $newReminder = $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
                }

                break;
        }

        $contact->logEvent('contact', $contact->id, 'update');

        dispatch(new ResizeAvatars($contact));

        $contact->updateGravatar();

        return redirect('/people/'.$contact->hashID())
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
        if ($contact->account_id != auth()->user()->account_id) {
            return redirect('/people/');
        }

        Relationship::where('contact_is', $contact->id)->delete();
        Relationship::where('of_contact', $contact->id)->delete();

        $contact->deleteEverything();

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
        return view('people.work.edit')
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

        return redirect('/people/'.$contact->hashID())
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
        return view('people.food-preferencies.edit')
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

        return redirect('/people/'.$contact->hashID())
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
            foreach ($results as $key => $result) {
                if ($result->is_partial) {
                    $real = $result->getRelatedRealContact();

                    $results[$key]->hash = $real->hashID();
                } else {
                    $results[$key]->hash = $result->hashID();
                }
            }

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
            Debugbar::disable();
        }

        $vcard = VCardHelper::prepareVCard($contact);

        return  $vcard->download();
    }

    /**
     * Set or change the frequency of which the user wants to stay in touch with
     * the given contact.
     *
     * @param  Request $request
     * @param  Contact $contact
     * @return [type]
     */
    public function stayInTouch(Request $request, Contact $contact)
    {
        $frequency = intval($request->get('frequency'));
        $state = $request->get('state');

        if (auth()->user()->account->hasLimitations()) {
            throw new Exception(trans('people.stay_in_touch_premium'));
        }

        // if not active, set frequency to 0
        if (! $state) {
            $frequency = 0;
        }
        $result = $contact->updateStayInTouchFrequency($frequency);

        if (! $result) {
            throw new Exception(trans('people.stay_in_touch_invalid'));
        }

        $contact->setStayInTouchTriggerDate($frequency, auth()->user()->timezone);

        return $frequency;
    }
}
