<?php

namespace App\Http\Controllers;

use Auth;
use App\Kid;
use App\Debt;
use App\Gift;
use App\Note;
use App\Task;
use App\Event;
use Validator;
use App\Contact;
use App\Country;
use App\Activity;
use App\Reminder;
use Carbon\Carbon;
use App\ActivityType;
use App\Http\Requests;
use App\SignificantOther;
use App\ActivityTypeGroup;
use App\Helpers\DateHelper;
use App\Jobs\ResizeAvatars;
use Illuminate\Http\Request;
use App\Helpers\RandomHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;

class PeopleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sort = Input::get('sort');
        $user = Auth::user();

        if ($user->contacts_sort_order !== $sort) {
            $user->updateContactViewPreference($sort);
        }

        $contacts = $user->account->contacts()->withCount('kids')->sortedBy($sort)->get();

        $data = [
            'contacts' => $contacts,
        ];

        return view('people.index', $data);
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

    /**
     * Store the note for this people.
     * @param  [type] $peopleId [description]
     * @return [type]           [description]
     */
    public function storeNote(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $body = $request->input('body');

        $contact->addNote($body);

        $request->session()->flash('success', trans('people.notes_add_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * @param int $contactId
     * @param int $noteId
     * @return \Illuminate\View\View
     */
    public function editNote($contactId, $noteId)
    {
        $contact = Contact::findOrFail($contactId);
        $note = Note::findOrFail($noteId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($note->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        return view('people.notes.edit', compact('contact', 'note'));
    }

    /**
     * Update a note
     * @param Request $request
     * @param int $contactId
     * @param int $noteId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateNote(Request $request, $contactId, $noteId)
    {
        $contact = Contact::findOrFail($contactId);
        $note = Note::findOrFail($noteId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($note->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        $note->body = $request->input('body');
        $note->save();

        $contact->logEvent('note', $note->id, 'update');

        $request->session()->flash('success', trans('people.notes_edit_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Delete the note.
     *
     * @param  Request $request
     * @param  int
     * @param  int
     */
    public function deleteNote(Request $request, $contactId, $noteId)
    {
        $contact = Contact::findOrFail($contactId);
        $note = Note::findOrFail($noteId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($note->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        $contact->deleteNote($noteId);

        $request->session()->flash('success', trans('people.notes_delete_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Display the Add Note view.
     *
     * @param int $contactId
     */
    public function addNote($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.notes.add', $data);
    }

    /**
     * Display the Add Kid view.
     *
     * @param int $contactId
     */
    public function addKid($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.dashboard.kids.add', $data);
    }

    /**
     * Add a kid to the database.
     * @param int
     * @return Response
     */
    public function storeKid(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $name = $request->input('firstname');
        $gender = $request->input('gender');
        $birthdateApproximate = $request->input('birthdateApproximate');

        if ($birthdateApproximate == 'approximate') {
            $age = $request->input('age');
            $birthdate = null;
        } elseif ($birthdateApproximate == 'unknown') {
            $age = null;
            $birthdate = null;
        } else {
            $age = null;
            $birthdate = $request->input('specificDate');
        }

        $kidId = $contact->addKid($name, $gender, $birthdateApproximate, $birthdate, $age, Auth::user()->timezone);

        // add reminder
        if ($birthdateApproximate !== 'approximate' and $birthdateApproximate !== 'unknown') {
            $reminder = $contact->reminders()->create([]);

            $reminder->title = trans('people.kids_add_birthday_reminder', ['name' => $name, 'contact_firstname' => $contact->getFirstName()]);
            $reminder->frequency_type = 'year';
            $reminder->frequency_number = 1;
            $reminder->next_expected_date = Carbon::createFromFormat('Y-m-d', $birthdate);
            $reminder->kid_id = $kidId;
            $reminder->account_id = $contact->account_id;
            $reminder->save();

            // date is in the past - we need to calculate next occuring date
            $reminder->calculateNextExpectedDate($reminder->next_expected_date, 'year', 1);
            $reminder->save();

            $contact->number_of_reminders = $contact->number_of_reminders + 1;
            $contact->save();
        }

        $request->session()->flash('success', trans('people.kids_add_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Show the Edit kid view.
     * @param  int $peopleId
     * @param  int $kidId
     * @return Response
     */
    public function editKid($contactId, $kidId)
    {
        $contact = Contact::findOrFail($contactId);
        $kid = Kid::findOrFail($kidId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($kid->child_of_contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
            'kid' => $kid,
        ];

        return view('people.dashboard.kids.edit', $data);
    }

    /**
     * Edit information the kid.
     * @param  int $peopleId
     * @param  int $kidId
     * @return Response
     */
    public function updateKid(Request $request, $contactId, $kidId)
    {
        $contact = Contact::findOrFail($contactId);
        $kid = Kid::findOrFail($kidId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($kid->child_of_contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        $name = $request->input('firstname');
        $gender = $request->input('gender');
        $birthdateApproximate = $request->input('birthdateApproximate');

        if ($birthdateApproximate === 'approximate') {
            $age = $request->input('age');
            $birthdate = null;
        } elseif ($birthdateApproximate === 'unknown') {
            $age = null;
            $birthdate = null;
        } else {
            $age = null;
            $birthdate = $request->input('specificDate');
        }

        $contact->editKid($kidId, $name, $gender, $birthdateApproximate, $birthdate, $age, Auth::user()->timezone);

        $request->session()->flash('success', trans('people.kids_update_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Delete a kid.
     * @param  Request $request
     * @param  int $peopleId
     * @param  int $kidId
     * @return Redirect
     */
    public function deleteKid(Request $request, $contactId, $kidId)
    {
        $contact = Contact::findOrFail($contactId);
        $kid = Kid::findOrFail($kidId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($kid->child_of_contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        $contact->deleteKid($kidId);

        $request->session()->flash('success', trans('people.kids_delete_success'));

        return redirect()->route('people.show', ['id' => $contact->id]);
    }

    /**
     * Show the Add significant other view.
     * @param int $peopleId
     */
    public function addSignificantOther($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id != Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if (!is_null($contact->getCurrentSignificantOther())) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.dashboard.significantother.add', $data);
    }

    /**
     * Add significant other.
     * @param Request $request
     * @param int
     * @return json
     */
    public function storeSignificantOther(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id != Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if (!is_null($contact->getCurrentSignificantOther())) {
            return redirect()->route('people.index');
        }

        $firstname = $request->input('firstname');
        $gender = $request->input('gender');
        $birthdateApproximate = $request->input('birthdateApproximate');

        if ($birthdateApproximate == 'approximate') {
            $age = $request->input('age');
            $birthdate = null;
        } elseif ($birthdateApproximate == 'unknown') {
            $age = null;
            $birthdate = null;
        } else {
            $age = null;
            $birthdate = $request->input('specificDate');
        }

        $contact->addSignificantOther($firstname, $gender, $birthdateApproximate, $birthdate, $age, Auth::user()->timezone);

        // add reminder
        if ($birthdateApproximate != 'approximate' and $birthdateApproximate != 'unknown') {
            $reminder = $contact->reminders()->create([]);

            $reminder->title = trans('people.significant_other_add_birthday_reminder', ['name' => $firstname, 'contact_firstname' => $contact->getFirstName()]);
            $reminder->frequency_type = 'year';
            $reminder->frequency_number = 1;
            $reminder->next_expected_date = Carbon::createFromFormat('Y-m-d', $birthdate);
            $reminder->account_id = $contact->account_id;
            $reminder->contact_id = $contact->id;
            $reminder->save();

            // date is in the past - we need to calculate next occuring date
            $reminder->calculateNextExpectedDate($reminder->next_expected_date, 'year', 1);
            $reminder->save();

            $contact->number_of_reminders = $contact->number_of_reminders + 1;
            $contact->save();
        }

        $request->session()->flash('success', trans('people.significant_other_add_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Show the Edit significant other view.
     *
     * @param  int $contactId
     * @param  int $significantOtherId
     * @return Response
     */
    public function editSignificantOther($contactId, $significantOtherId)
    {
        $contact = Contact::findOrFail($contactId);
        $significantOther = SignificantOther::findOrFail($significantOtherId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($significantOther->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.dashboard.significantother.edit', $data);
    }

    /**
     * Update the significant other information.
     *
     * @param  Request $request
     * @param  int $contactId
     * @param  int $significantOtherId
     * @return Response
     */
    public function updateSignificantOther(Request $request, $contactId, $significantOtherId)
    {
        $contact = Contact::findOrFail($contactId);
        $significantOther = SignificantOther::findOrFail($significantOtherId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($significantOther->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        $firstname = $request->input('firstname');
        $gender = $request->input('gender');
        $birthdateApproximate = $request->input('birthdateApproximate');

        if ($birthdateApproximate == 'approximate') {
            $age = $request->input('age');
            $birthdate = null;
        } elseif ($birthdateApproximate == 'unknown') {
            $age = null;
            $birthdate = null;
        } else {
            $age = null;
            $birthdate = $request->input('specificDate');
        }

        $significantOtherId = $contact->editSignificantOther($significantOther->id, $firstname, $gender, $birthdateApproximate, $birthdate, $age, Auth::user()->timezone);

        $request->session()->flash('success', trans('people.significant_other_edit_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Removes the significant other.
     * @param  Request $request
     * @param  int $peopleId
     * @return string
     */
    public function deleteSignificantOther(Request $request, $contactId, $significantOtherId)
    {
        $contact = Contact::findOrFail($contactId);
        $significantOther = SignificantOther::findOrFail($significantOtherId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($significantOther->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        $contact->deleteSignificantOther($significantOtherId);
        $request->session()->flash('success', trans('people.significant_other_delete_success'));

        return redirect()->route('people.show', ['id' => $contact->id]);
    }

    /**
     * Show the Add money view.
     * @param int $peopleId
     */
    public function addDebt($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.debt.add', $data);
    }

    public function editDebt(Request $request, $contactId, $debtId)
    {
        $contact = Contact::findOrFail($contactId);
        $debt = Debt::findOrFail($debtId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($debt->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        return view('people.debt.edit', compact('debt', 'contact'));
    }

    /**
     * Actually store the debt.
     * @param  Request $request
     * @param  int $peopleId
     * @return
     */
    public function storeDebt(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $amount = $request->input('amount');
        $reason = $request->input('reason');
        $indebt = $request->input('in-debt');

        $debt = new Debt;
        $debt->account_id = $contact->account_id;
        $debt->contact_id = $contact->id;
        $debt->in_debt = $indebt;
        $debt->amount = $amount;
        if ($reason !== '') {
            $debt->reason = $reason;
        }

        $debt->save();

        $contact->logEvent('debt', $debt->id, 'create');

        $request->session()->flash('success', trans('people.debt_add_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Update stored debt.
     * @param  Request $request
     * @param  int $contactId
     * @param  int $debtId
     */
    public function updateDebt(Request $request, $contactId, $debtId)
    {
        $contact = Contact::findOrFail($contactId);
        $debt = Debt::findOrFail($debtId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($debt->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        $debt->in_debt = $request->input('in-debt');
        $debt->amount = $request->input('amount');
        $debt->reason = $request->input('reason');

        $debt->save();

        $contact->logEvent('debt', $debt->id, 'update');

        $request->session()->flash('success', trans('people.debt_edit_success'));

        return redirect('/people/' . $contact->id);
    }

    public function deleteDebt(Request $request, $contactId, $debtId)
    {
        $contact = Contact::findOrFail($contactId);
        $debt = Debt::findOrFail($debtId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($debt->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        // Delete all events
        $contact->events()
            ->where('object_type', 'debt')
            ->where('object_id', $debt->id)
            ->get()
            ->each
            ->delete();

        $debt->delete();

        $request->session()->flash('success', trans('people.debt_delete_success'));

        return redirect('/people/' . $contact->id);
    }
}
