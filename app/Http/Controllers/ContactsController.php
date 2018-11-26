<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Jobs\ResizeAvatars;
use App\Models\Contact\Tag;
use Illuminate\Http\Request;
use App\Helpers\AvatarHelper;
use App\Helpers\SearchHelper;
use App\Models\Contact\Contact;
use App\Services\VCard\ExportVCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Relationship\Relationship;
use Barryvdh\Debugbar\Facade as Debugbar;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Contact\ContactShort as ContactResource;

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
        return $this->contacts($request, true);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function archived(Request $request)
    {
        return $this->contacts($request, false);
    }

    /**
     * Display contacts.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    private function contacts(Request $request, bool $active)
    {
        $user = $request->user();
        $sort = $request->get('sort') ?? $user->contacts_sort_order;
        $showDeceased = $request->get('show_dead');

        if ($user->contacts_sort_order !== $sort) {
            $user->updateContactViewPreference($sort);
        }

        $tags = null;
        $url = '';
        $count = 1;

        $contacts = $user->account->contacts()->real();
        if ($active) {
            $nbArchived = $contacts->count();
            $contacts = $contacts->active();
            $nbArchived = $nbArchived - $contacts->count();
        } else {
            $contacts = $contacts->notActive();
            $nbArchived = $contacts->count();
        }

        if ($request->get('no_tag')) {
            //get tag less contacts
            $contacts = $contacts->tags('NONE');
        } elseif ($request->get('tag1')) {
            // get contacts with selected tags

            $tags = collect();

            while ($request->get('tag'.$count)) {
                $tag = Tag::where('account_id', auth()->user()->account_id)
                            ->where('name_slug', $request->get('tag'.$count))
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

            $contacts = $contacts->tags($tags);
        }
        $contacts = $contacts->sortedBy($sort)->get();

        // count the deceased
        $deceasedCount = $contacts->filter(function ($item) {
            return $item->is_dead === true;
        })->count();

        // filter out deceased if necessary
        if ($showDeceased != 'true') {
            $contacts = $contacts->filter(function ($item) {
                return $item->is_dead === false;
            });
        }

        // starred contacts
        $starredContacts = $contacts->filter(function ($item) {
            return $item->is_starred === true;
        });

        $unstarredContacts = $contacts->filter(function ($item) {
            return $item->is_starred === false;
        });

        return view('people.index')
            ->with('hidingDeceased', $showDeceased != 'true')
            ->with('deceasedCount', $deceasedCount)
            ->withContacts($contacts->unique('id'))
            ->withUnstarredContacts($unstarredContacts)
            ->withStarredContacts($starredContacts)
            ->withActive($active)
            ->withHasArchived($nbArchived > 0)
            ->withArchivedCOntacts($nbArchived)
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
        if (auth()->user()->account->hasReachedContactLimit()
        && auth()->user()->account->hasLimitations()
        && ! auth()->user()->account->legacy_free_plan_unlimited_contacts) {
            return redirect()->route('settings.subscriptions.index');
        }

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
        $contact->account_id = $request->user()->account_id;
        $contact->gender_id = $request->input('gender');

        $contact->first_name = $request->input('first_name');
        $contact->last_name = $request->input('last_name', null);
        $contact->nickname = $request->input('nickname', null);

        $contact->setAvatarColor();
        $contact->save();

        // Did the user press "Save" or "Submit and add another person"
        if (! is_null($request->get('save'))) {
            return redirect()->route('people.show', $contact);
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
            return redirect()->route('people.index');
        }
        $contact->load(['notes' => function ($query) {
            $query->orderBy('updated_at', 'desc');
        }]);

        $contact->last_consulted_at = now(DateHelper::getTimezone());
        $contact->number_of_views = $contact->number_of_views + 1;
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

        // add `---` at the top of the dropdowns
        $days = DateHelper::getListOfDays();
        $days->prepend([
            'id' => 0,
            'name' => '---',
        ]);

        $months = DateHelper::getListOfMonths();
        $months->prepend([
            'id' => 0,
            'name' => '---',
        ]);

        return view('people.profile')
            ->withLoveRelationships($loveRelationships)
            ->withFamilyRelationships($familyRelationships)
            ->withFriendRelationships($friendRelationships)
            ->withWorkRelationships($workRelationships)
            ->withReminders($reminders)
            ->withModules($modules)
            ->withAvatar(AvatarHelper::get($contact, 87))
            ->withContact($contact)
            ->withDays($days)
            ->withMonths($months)
            ->withYears(DateHelper::getListOfYears());
    }

    /**
     * Display the Edit people's view.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact)
    {
        $now = now();
        $age = (string) (! is_null($contact->birthdate) ? $contact->birthdate->getAge() : 0);
        $birthdate = ! is_null($contact->birthdate) ? $contact->birthdate->date->toDateString() : $now->toDateString();
        $day = ! is_null($contact->birthdate) ? $contact->birthdate->date->day : $now->day;
        $month = ! is_null($contact->birthdate) ? $contact->birthdate->date->month : $now->month;

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
            'description' => 'max:240',
            'gender' => 'required',
            'file' => 'max:10240',
            'birthdate' => 'required|string',
            'birthdayDate' => 'date_format:Y-m-d',
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
        $contact->description = $request->input('description');
        $contact->nickname = $request->input('nickname', null);

        if ($request->file('avatar') != '') {
            if ($contact->has_avatar) {
                try {
                    $contact->deleteAvatars();
                } catch (\Exception $e) {
                    return back()
                        ->withInput()
                        ->withErrors(trans('app.error_save'));
                }
            }

            $contact->has_avatar = true;
            $contact->avatar_location = config('filesystems.default');
            $contact->avatar_file_name = $request->avatar->storePublicly('avatars', $contact->avatar_location);
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
                    $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
                }

                break;
            case 'exact':
                $birthdate = $request->input('birthdayDate');
                $birthdate = DateHelper::parseDate($birthdate);
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

        dispatch(new ResizeAvatars($contact));

        $contact->updateGravatar();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.information_edit_success'));
    }

    /**
     * Delete the specified resource.
     *
     * @param Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Contact $contact)
    {
        if ($contact->account_id != auth()->user()->account_id) {
            return redirect()->route('people.index');
        }

        Relationship::where('account_id', auth()->user()->account_id)
            ->where('contact_is', $contact->id)
            ->delete();
        Relationship::where('account_id', auth()->user()->account_id)
            ->where('of_contact', $contact->id)
            ->delete();

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

        return redirect()->route('people.show', $contact)
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
            ->withAvatar(AvatarHelper::get($contact, 87))
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

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.food_preferencies_add_success'));
    }

    /**
     * Search used in the header.
     * @param  Request $request
     */
    public function search(Request $request)
    {
        $needle = $request->needle;

        if ($needle == null) {
            return;
        }

        $results = SearchHelper::searchContacts($needle, 20, 'created_at');

        if (count($results) !== 0) {
            return ContactResource::collection($results);
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

        $vcard = (new ExportVCard)->execute([
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
        ]);

        return response($vcard->serialize())
            ->header('Content-type', 'text/x-vcard')
            ->header('Content-Disposition', 'attachment; filename='.str_slug($contact->name).'.vcf');
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
            throw new \LogicException(trans('people.stay_in_touch_premium'));
        }

        // if not active, set frequency to 0
        if (! $state) {
            $frequency = 0;
        }
        $result = $contact->updateStayInTouchFrequency($frequency);

        if (! $result) {
            throw new \LogicException(trans('people.stay_in_touch_invalid'));
        }

        $contact->setStayInTouchTriggerDate($frequency, DateHelper::getTimezone());

        return $frequency;
    }

    /**
     * Toggle favorites of a contact.
     * @param  Request $request
     * @param  Contact $contact
     * @return array
     */
    public function favorite(Request $request, Contact $contact)
    {
        $bool = (bool) $request->get('toggle');

        $contact->is_starred = $bool;
        $contact->save();

        return [
            'is_starred' => $bool,
        ];
    }

    /**
     * Toggle archive state of a contact.
     *
     * @param  Request $request
     * @param  Contact $contact
     * @return array
     */
    public function archive(Request $request, Contact $contact)
    {
        $contact->is_active = ! $contact->is_active;
        $contact->save();

        return [
            'is_active' => $contact->is_active,
        ];
    }
}
