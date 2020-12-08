<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Helpers\DateHelper;
use App\Helpers\FormHelper;
use App\Models\Contact\Tag;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\GenderHelper;
use App\Helpers\LocaleHelper;
use App\Helpers\SearchHelper;
use App\Helpers\AccountHelper;
use App\Helpers\StorageHelper;
use App\Models\Contact\Contact;
use App\Services\VCard\ExportVCard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Jobs\UpdateLastConsultedDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use App\Models\Relationship\Relationship;
use Barryvdh\Debugbar\Facade as Debugbar;
use App\Services\User\UpdateViewPreference;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\CreateContact;
use App\Services\Contact\Contact\UpdateContact;
use App\Services\Contact\Contact\DestroyContact;
use App\Services\Contact\Contact\UpdateWorkInformation;
use App\Services\Contact\Contact\UpdateContactFoodPreferences;
use App\Http\Resources\Contact\ContactSearch as ContactResource;

class ContactsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return View|RedirectResponse
     */
    public function index(Request $request)
    {
        return $this->contacts($request, true);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return View|RedirectResponse
     */
    public function archived(Request $request)
    {
        return $this->contacts($request, false);
    }

    /**
     * Display contacts.
     *
     * @param Request $request
     * @param bool $active
     * @return View|RedirectResponse
     */
    private function contacts(Request $request, bool $active)
    {
        $user = $request->user();
        $sort = $request->input('sort') ?? $user->contacts_sort_order;
        $showDeceased = $request->input('show_dead');

        if ($user->contacts_sort_order !== $sort) {
            app(UpdateViewPreference::class)->execute([
                'account_id' => $user->account_id,
                'user_id' => $user->id,
                'preference' => $sort,
            ]);
        }

        $contacts = $user->account->contacts()->real();
        if ($active) {
            $archived = (clone $contacts)->notActive();
            $contacts = (clone $contacts)->active();
            $nbArchived = $archived->count();
        } else {
            $contacts = $contacts->notActive();
            $nbArchived = $contacts->count();
        }

        $tags = null;
        $url = '';
        $count = 1;

        if ($request->input('tag1')) {

            // get contacts with selected tags
            $tags = collect();

            while ($request->input('tag'.$count)) {
                $tag = Tag::where([
                    'account_id' => auth()->user()->account_id,
                    'name_slug' => $request->input('tag'.$count),
                ]);
                if ($tag->count() > 0) {
                    $tag = $tag->get();

                    if (! $tags->contains($tag[0])) {
                        $tags = $tags->concat($tag);
                    }

                    $url .= 'tag'.$count.'='.$tag[0]->name_slug.'&';
                }
                $count++;
            }
            if ($tags->count() === 0) {
                return redirect()->route('people.index');
            } else {
                $contacts = $contacts->tags($tags);
            }
        } elseif ($request->input('no_tag')) {
            $contacts = $contacts->tags('NONE');
        }

        $contactsCount = (clone $contacts)->alive()->count();
        $deceasedCount = (clone $contacts)->dead()->count();

        if ($showDeceased === 'true') {
            $contactsCount += $deceasedCount;
        }

        $accountHasLimitations = AccountHelper::hasLimitations(auth()->user()->account);

        return view('people.index')
            ->withAccountHasLimitations($accountHasLimitations)
            ->with('hidingDeceased', $showDeceased != 'true')
            ->with('deceasedCount', $deceasedCount)
            ->withActive($active)
            ->withContactsCount($contactsCount)
            ->withHasArchived($nbArchived > 0)
            ->withArchivedContacts($nbArchived)
            ->withTags($tags)
            ->withTagsCount(Tag::contactsCount())
            ->withUrl($url)
            ->withTagCount($count)
            ->withTagLess($request->input('no_tag') ?? false);
    }

    /**
     * Show the form to add a new contact.
     *
     * @param Request $request
     * @return View|Factory|RedirectResponse
     */
    public function create(Request $request)
    {
        return $this->createForm($request, false);
    }

    /**
     * Show the form in case the contact is missing.
     *
     * @param Request $request
     * @return View|Factory|RedirectResponse
     */
    public function missing(Request $request)
    {
        return $this->createForm($request, true);
    }

    /**
     * Show the Add user form unless the contact has limitations.
     *
     * @param Request $request
     * @param  bool $isContactMissing
     * @return View|Factory|RedirectResponse
     */
    private function createForm(Request $request, bool $isContactMissing = false)
    {
        if (AccountHelper::hasReachedContactLimit(auth()->user()->account)
            && AccountHelper::hasLimitations(auth()->user()->account)
            && ! auth()->user()->account->legacy_free_plan_unlimited_contacts) {
            return redirect()->route('settings.subscriptions.index');
        }

        $accountHasLimitations = AccountHelper::hasLimitations(auth()->user()->account);

        return view('people.create')
            ->withAccountHasLimitations($accountHasLimitations)
            ->withIsContactMissing($isContactMissing)
            ->withGenders(GenderHelper::getGendersInput())
            ->withDefaultGender(auth()->user()->account->default_gender_id)
            ->withFormNameOrder(FormHelper::getNameOrderForForms(auth()->user()))
            ->withFirstName($request->input('first_name'))
            ->withLastName($request->input('last_name'));
    }

    /**
     * Store the contact.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            $contact = app(CreateContact::class)->execute([
                'account_id' => auth()->user()->account_id,
                'author_id' => auth()->user()->id,
                'first_name' => $request->input('first_name'),
                'middle_name' => $request->input('middle_name', null),
                'last_name' => $request->input('last_name', null),
                'nickname' => $request->input('nickname', null),
                'gender_id' => $request->input('gender'),
                'is_birthdate_known' => false,
                'is_deceased' => false,
                'is_deceased_date_known' => false,
            ]);
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->validator);
        }

        // Did the user press "Save" or "Submit and add another person"
        if (! is_null($request->input('save'))) {
            return redirect()->route('people.show', $contact);
        } else {
            return redirect()->route('people.create')
                            ->with('status', trans('people.people_add_success', ['name' => $contact->name]));
        }
    }

    /**
     * Display the contact profile.
     *
     * @param Contact $contact
     *
     * @return View|RedirectResponse
     */
    public function show(Contact $contact)
    {
        // make sure we don't display a partial contact
        if ($contact->is_partial) {
            $realContact = $contact->getRelatedRealContact();
            if (is_null($realContact)) {
                return redirect()->route('people.index')
                    ->withErrors(trans('people.people_not_found'));
            }

            return redirect()->route('people.show', $realContact);
        }
        $contact->load(['notes' => function ($query) {
            $query->orderBy('updated_at', 'desc');
        }]);

        UpdateLastConsultedDate::dispatch($contact);

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
        $reminders = $contact->activeReminders;
        $relevantRemindersFromRelatedContacts = $contact->getBirthdayRemindersAboutRelatedContacts();
        $reminders = $reminders->merge($relevantRemindersFromRelatedContacts);
        // now we need to sort the reminders by next date they will be triggered
        foreach ($reminders as $reminder) {
            $next_expected_date = $reminder->calculateNextExpectedDateOnTimezone();
            $reminder->next_expected_date_human_readable = DateHelper::getShortDate($next_expected_date);
            $reminder->next_expected_date = DateHelper::getDate($next_expected_date);
        }
        $reminders = $reminders->sortBy('next_expected_date');

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

        $hasReachedAccountStorageLimit = StorageHelper::hasReachedAccountStorageLimit($contact->account);
        $accountHasLimitations = AccountHelper::hasLimitations($contact->account);

        return view('people.profile')
            ->withHasReachedAccountStorageLimit($hasReachedAccountStorageLimit)
            ->withAccountHasLimitations($accountHasLimitations)
            ->withLoveRelationships($loveRelationships)
            ->withFamilyRelationships($familyRelationships)
            ->withFriendRelationships($friendRelationships)
            ->withWorkRelationships($workRelationships)
            ->withReminders($reminders)
            ->withModules($modules)
            ->withContact($contact)
            ->withWeather($contact->getWeather())
            ->withDays($days)
            ->withMonths($months)
            ->withYears(DateHelper::getListOfYears());
    }

    /**
     * Display the Edit people's view.
     *
     * @param Contact $contact
     *
     * @return View
     */
    public function edit(Contact $contact)
    {
        $now = now();
        $age = (string) (! is_null($contact->birthdate) ? $contact->birthdate->getAge() : 0);
        $birthdate = ! is_null($contact->birthdate) ? $contact->birthdate->date->toDateString() : $now->toDateString();
        $deceaseddate = ! is_null($contact->deceasedDate) ? $contact->deceasedDate->date->toDateString() : '';
        $day = ! is_null($contact->birthdate) ? $contact->birthdate->date->day : $now->day;
        $month = ! is_null($contact->birthdate) ? $contact->birthdate->date->month : $now->month;

        $hasBirthdayReminder = ! is_null($contact->birthday_reminder_id);
        $hasDeceasedReminder = ! is_null($contact->deceased_reminder_id);

        $accountHasLimitations = AccountHelper::hasLimitations(auth()->user()->account);

        return view('people.edit')
            ->withAccountHasLimitations($accountHasLimitations)
            ->withContact($contact)
            ->withDays(DateHelper::getListOfDays())
            ->withMonths(DateHelper::getListOfMonths())
            ->withBirthdayState($contact->getBirthdayState())
            ->withBirthdate($birthdate)
            ->withDeceaseddate($deceaseddate)
            ->withDay($day)
            ->withMonth($month)
            ->withAge($age)
            ->withHasBirthdayReminder($hasBirthdayReminder)
            ->withHasDeceasedReminder($hasDeceasedReminder)
            ->withGenders(GenderHelper::getGendersInput())
            ->withFormNameOrder(FormHelper::getNameOrderForForms(auth()->user()));
    }

    /**
     * Update the contact.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @return RedirectResponse
     */
    public function update(Request $request, Contact $contact)
    {
        // process birthday dates
        // TODO: remove this part entirely when we redo this whole SpecialDate
        // thing
        if ($request->input('birthdate') == 'exact') {
            $birthdate = $request->input('birthdayDate');
            $birthdate = DateHelper::parseDate($birthdate);
            $day = $birthdate->day;
            $month = $birthdate->month;
            $year = $birthdate->year;
        } else {
            $day = $request->input('day');
            $month = $request->input('month');
            $year = $request->input('year');
        }
        $is_deceased_date_known = false;
        if ($request->input('is_deceased_date_known') === 'true' && $request->input('deceased_date')) {
            $is_deceased_date_known = true;
            $deceased_date = $request->input('deceased_date');
            $deceased_date = DateHelper::parseDate($deceased_date);
            $deceased_date_day = $deceased_date->day;
            $deceased_date_month = $deceased_date->month;
            $deceased_date_year = $deceased_date->year;
        } else {
            $deceased_date_day = $deceased_date_month = $deceased_date_year = null;
        }
        if (! empty($request->input('is_deceased'))) {
            //if the contact has died, disable StayInTouch
            $contact->updateStayInTouchFrequency(0);
            $contact->setStayInTouchTriggerDate(0);
        }

        $data = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'first_name' => $request->input('firstname'),
            'middle_name' => $request->input('middlename', null),
            'last_name' => $request->input('lastname', null),
            'nickname' => $request->input('nickname', null),
            'gender_id' => $request->input('gender'),
            'description' => $request->input('description', null),
            'is_birthdate_known' => ! empty($request->input('birthdate')) && $request->input('birthdate') !== 'unknown',
            'birthdate_day' => $day,
            'birthdate_month' => $month,
            'birthdate_year' => $year,
            'birthdate_is_age_based' => $request->input('birthdate') === 'approximate',
            'birthdate_age' => $request->input('age'),
            'birthdate_add_reminder' => ! empty($request->input('addReminder')),
            'is_deceased' => ! empty($request->input('is_deceased')),
            'is_deceased_date_known' => $is_deceased_date_known,
            'deceased_date_day' => $deceased_date_day,
            'deceased_date_month' => $deceased_date_month,
            'deceased_date_year' => $deceased_date_year,
            'deceased_date_add_reminder' => ! empty($request->input('add_reminder_deceased')),
        ];

        $contact = app(UpdateContact::class)->execute($data);

        if ($request->file('avatar') != '') {
            if ($contact->has_avatar) {
                try {
                    $contact->deleteAvatars();
                } catch (\Exception $e) {
                    Log::warning(__CLASS__.' update: Failed to delete avatars', [
                        'contact' => $contact,
                        'exception' => $e,
                    ]);
                }
            }
            $contact->has_avatar = true;
            $contact->avatar_location = config('filesystems.default');
            $contact->avatar_file_name = $request->avatar->storePublicly('avatars', $contact->avatar_location);
            $contact->save();
        }

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.information_edit_success'));
    }

    /**
     * Delete the contact.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request, Contact $contact)
    {
        if ($contact->account_id != auth()->user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
        ];

        app(DestroyContact::class)->execute($data);

        return redirect()->route('people.index')
            ->with('success', trans('people.people_delete_success'));
    }

    /**
     * Show the Edit work view.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @return View
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
     *
     * @return RedirectResponse
     */
    public function updateWork(Request $request, Contact $contact)
    {
        $contact = app(UpdateWorkInformation::class)->execute([
            'account_id' => auth()->user()->account_id,
            'author_id' => auth()->user()->id,
            'contact_id' => $contact->id,
            'job' => $request->input('job'),
            'company' => $request->input('company'),
        ]);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.work_edit_success'));
    }

    /**
     * Show the Edit food preferences view.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @return View
     */
    public function editFoodPreferences(Request $request, Contact $contact)
    {
        $accountHasLimitations = AccountHelper::hasLimitations(auth()->user()->account);

        return view('people.food-preferences.edit')
            ->withAccountHasLimitations($accountHasLimitations)
            ->withContact($contact);
    }

    /**
     * Save the food preferences.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @return RedirectResponse
     */
    public function updateFoodPreferences(Request $request, Contact $contact)
    {
        $contact = app(UpdateContactFoodPreferences::class)->execute([
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'food_preferences' => $request->input('food'),
        ]);

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.food_preferences_add_success'));
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

        $results = SearchHelper::searchContacts($needle, 'created_at')
            ->paginate(20);

        if ($results->total() > 0) {
            return ContactResource::collection($results);
        } else {
            return ['noResults' => trans('people.people_search_no_results')];
        }
    }

    /**
     * Download the contact as vCard.
     * @param  Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function vCard(Contact $contact)
    {
        if (config('app.debug')) {
            Debugbar::disable();
        }

        $vcard = app(ExportVCard::class)->execute([
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
        ]);

        return response($vcard->serialize())
            ->header('Content-type', 'text/x-vcard')
            ->header('Content-Disposition', 'attachment; filename='.Str::slug($contact->name, '-', LocaleHelper::getLang()).'.vcf');
    }

    /**
     * Set or change the frequency of which the user wants to stay in touch with
     * the given contact.
     *
     * @param  Request $request
     * @param  Contact $contact
     * @return int
     */
    public function stayInTouch(Request $request, Contact $contact)
    {
        $frequency = intval($request->input('frequency'));
        $state = $request->input('state');

        if (AccountHelper::hasLimitations(auth()->user()->account)) {
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

        $contact->setStayInTouchTriggerDate($frequency);

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
        $bool = (bool) $request->input('toggle');

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

    /**
     * Display the list of contacts.
     *
     * @param Request $request
     * @return array
     */
    public function list(Request $request)
    {
        $accountId = auth()->user()->account_id;

        $user = $request->user();
        $sort = $request->input('sort') ?? $user->contacts_sort_order;

        if ($user->contacts_sort_order !== $sort) {
            app(UpdateViewPreference::class)->execute([
                'account_id' => $user->account_id,
                'user_id' => $user->id,
                'preference' => $sort,
            ]);
        }

        $tags = null;
        $url = '';
        $count = 1;

        $contacts = $user->account->contacts()->real();

        // filter out archived contacts if necessary
        if ($request->input('show_archived') != 'true') {
            $contacts = $contacts->active();
        } else {
            $contacts = $contacts->notActive();
        }

        // filter out deceased if necessary
        if ($request->input('show_dead') != 'true') {
            $contacts = $contacts->alive();
        }

        if ($request->input('no_tag')) {
            // get tag less contacts
            $contacts = $contacts->tags('NONE');
        } elseif ($request->input('tag1')) {
            // get contacts with selected tags
            $tags = collect();

            while ($request->input('tag'.$count)) {
                $tag = Tag::where([
                    'account_id' => $accountId,
                    'name_slug' => $request->input('tag'.$count),
                ])->get();

                if (! ($tags->contains($tag[0]))) {
                    $tags = $tags->concat($tag);
                }

                $url = $url.'tag'.$count.'='.$tag[0]->name_slug.'&';

                $count++;
            }
            if ($tags->count() > 0) {
                $contacts = $contacts->tags($tags);
            }
        }

        // get the number of contacts per page
        $perPage = $request->has('perPage') ? $request->input('perPage') : config('monica.number_of_contacts_pagination');

        // search contacts
        $contacts = $contacts->search($request->input('search') ?? '', $accountId, 'is_starred', 'desc', $sort)
            ->paginate($perPage);

        return [
            'totalRecords' => $contacts->total(),
            'contacts' => ContactResource::collection($contacts),
        ];
    }
}
