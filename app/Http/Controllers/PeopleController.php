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
     * Display the activities page.
     * @param  int $id ID of the People object
     * @return view
     */
    public function activities($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.activities.index', $data);
    }

    /**
     * Show the Add activity screen.
     * @param int $id ID of the people object
     */
    public function addActivity($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.activities.add', $data);
    }

    /**
     * Store the activity for the People object.
     * @param int $id
     * @return
     */
    public function storeActivity(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $summary = $request->input('summary');
        $activityTypeId = $request->input('activityType');
        $description = $request->input('comment');
        $dateItHappened = Carbon::createFromFormat('Y-m-d', $request->input('specific_date'));

        $activity = new Activity;
        $activity->summary = $summary;
        $activity->account_id = $contact->account_id;
        $activity->contact_id = $contact->id;

        if ($activityTypeId == 0) {
            $activity->activity_type_id = null;
        } else {
            $activity->activity_type_id = $activityTypeId;
        }

        if ($description == null) {
            $activity->description = null;
        } else {
            $activity->description = $description;
        }
        $activity->date_it_happened = $dateItHappened;
        $activity->save();

        $request->session()->flash('success', trans('people.activities_add_success'));

        // Event
        $contact->logEvent('activity', $activity->id, 'create');

        // Update statistics about activities
        $contact->calculateActivitiesStatistics();

        // Increment the number of activities
        $contact->number_of_activities = $contact->number_of_activities + 1;
        $contact->save();

        return redirect('/people/' . $contact->id);
    }

    /**
     * Edit the activity.
     */
    public function editActivity($contactId, $activityId)
    {
        $contact = Contact::findOrFail($contactId);
        $activity = Activity::findOrFail($activityId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($contact->account_id !== $activity->account_id) {
            return redirect()->route('people.index');
        }

        if ($contact->id != $activity->contact_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
            'activity' => $activity,
        ];

        return view('people.activities.edit', $data);
    }

    /**
     * Save the updated activity.
     * @param int $id
     * @return
     */
    public function updateActivity(Request $request, $contactId, $activityId)
    {
        $contact = Contact::findOrFail($contactId);
        $activity = Activity::findOrFail($activityId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($contact->account_id !== $activity->account_id) {
            return redirect()->route('people.index');
        }

        if ($contact->id !== $activity->contact_id) {
            return redirect()->route('people.index');
        }


        $activity->summary = $request->input('summary');
        $activityTypeId = $request->input('activityType');

        if ($activityTypeId == 0) {
            $activity->activity_type_id = null;
        } else {
            $activity->activity_type_id = $request->input('activityType');
        }

        $description = $request->input('comment');
        if ($description == null || $description == '') {
            $activity->description = null;
        } else {
            $activity->description = $description;
        }
        $activity->date_it_happened = Carbon::createFromFormat('Y-m-d', $request->input('specific_date'));
        $activity->save();

        // Event
        $contact->logEvent('activity', $activity->id, 'update');

        // Calculate the statistics about activities
        $contact->calculateActivitiesStatistics();

        $request->session()->flash('success', trans('people.activities_update_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Delete the activity.
     * @param int $id
     * @return
     */
    public function deleteActivity(Request $request, $contactId, $activityId)
    {
        $contact = Contact::findOrFail($contactId);
        $activity = Activity::findOrFail($activityId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($contact->account_id !== $activity->account_id) {
            return redirect()->route('people.index');
        }

        if ($contact->id !== $activity->contact_id) {
            return redirect()->route('people.index');
        }

        $activity->delete();

        // Decrease number of activities
        $contact->number_of_activities = $contact->number_of_activities - 1;
        if ($contact->number_of_activities < 1) {
            $contact->number_of_activities = 0;
        }
        $contact->save();

        // Calculate the statistics about activities
        $contact->calculateActivitiesStatistics();

        $request->session()->flash('success', trans('people.activities_delete_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Display the reminders page.
     * @param  int $id ID of the People object
     *
     * @return view
     */
    public function reminders($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.reminders.index', $data);
    }

    /**
     * Show the Add reminder screen.
     * @param int $id ID of the people object
     */
    public function addReminder($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.reminders.add', $data);
    }

    /**
     * Add a reminder.
     * @param Request $request
     * @param int
     */
    public function storeReminder(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $reminderText = $request->input('reminder');
        $reminderNextExpectedDate = $request->input('reminderNextExpectedDate');

        $frequencyType = $request->input('frequencyType');
        $frequencyRecurringNumber = $request->input('frequencyRecurringNumber');

        $reminderRecurringFrequency = $request->input('reminderRecurringFrequency');
        $comment = $request->input('comment');

        // Create the reminder
        $reminder = new Reminder;

        $reminder->title = $reminderText;

        if ($comment != '') {
            $reminder->description = $comment;
        }

        if ($frequencyType == 'once') {
            $reminder->frequency_type = 'one_time';
        } else {
            $reminder->frequency_type = $reminderRecurringFrequency;
            $reminder->frequency_number = $frequencyRecurringNumber;
        }

        $reminder->next_expected_date = $reminderNextExpectedDate;
        $reminder->account_id = $contact->account_id;
        $reminder->contact_id = $contact->id;
        $reminder->save();

        $request->session()->flash('success', trans('people.reminders_create_success'));

        // log event
        $contact->logEvent('reminder', $reminder->id, 'create');

        // increment number of tasks
        $contact->number_of_reminders = $contact->number_of_reminders + 1;
        $contact->save();

        return redirect('/people/' . $contact->id);
    }

    /**
     * Delete the reminder.
     */
    public function deleteReminder(Request $request, $contactId, $reminderId)
    {
        $contact = Contact::findOrFail($contactId);
        $reminder = Reminder::findOrFail($reminderId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($reminder->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        // Delete all events
        $contact->events()
            ->where('object_type', 'reminder')
            ->where('object_id', $reminder->id)
            ->get()
            ->each
            ->delete();


        // Decrease number of reminders
        $contact->number_of_reminders = $contact->number_of_reminders - 1;
        $contact->save();

        $reminder->delete();

        $request->session()->flash('success', trans('people.reminders_delete_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Display the tasks page.
     * @param  int $id ID of the People object
     * @return Response
     */
    public function tasks($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.tasks.index', $data);
    }

    /**
     * Show the Add task view.
     * @param Request $request
     * @param int
     */
    public function addTask($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.tasks.add', $data);
    }

    /**
     * Add a task.
     * @param Request $request
     * @param int
     */
    public function storeTask(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        $task = new Task;
        $task->account_id = $contact->account_id;
        $task->contact_id = $contact->id;
        $task->title = $request->input('title');

        if ($request->input('comment') != '') {
            $task->description = $request->input('comment');
        } else {
            $task->description = null;
        }

        $task->status = 'inprogress';
        $task->save();

        // increment number of tasks
        $contact->number_of_tasks_in_progress = $contact->number_of_tasks_in_progress + 1;
        $contact->save();

        // log task add
        $contact->logEvent('task', $task->id, 'create');

        $request->session()->flash('success', trans('people.tasks_add_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Toggle a task between being complete and in progress.
     * @param Request $request
     * @param int
     */
    public function toggleTask(Request $request, $contactId, $taskId)
    {
        $contact = Contact::findOrFail($contactId);
        $task = Task::findOrFail($taskId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($task->contact_id != $contact->id) {
            return redirect()->route('people.index');
        }

        $task->toggle();

        $request->session()->flash('success', trans('people.tasks_complete_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Mark the task as deleted.
     * @param  Request $request
     * @param  int $peopleId
     * @param  int $taskId
     */
    public function deleteTask(Request $request, $contactId, $taskId)
    {
        $contact = Contact::findOrFail($contactId);
        $task = Task::findOrFail($taskId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($task->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        $task->delete();

        // Delete all events
        $events = $contact->events()
            ->where('object_type', 'task')
            ->where('object_id', $task->id)
            ->get()
            ->each
            ->delete();

        // Decrease number of notes
        if ($task->status == 'inprogress') {
            $contact->number_of_tasks_in_progress = $contact->number_of_tasks_in_progress - 1;
        } else {
            $contact->number_of_tasks_completed = $contact->number_of_tasks_completed - 1;
        }

        $request->session()->flash('success', trans('people.tasks_delete_success'));
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
     * Show the Gifts page.
     * @param  int $peopleId
     * @return
     */
    public function gifts($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.gifts.index', $data);
    }

    /**
     * Show the Add gift screen.
     * @param int $id ID of the people object
     */
    public function addGift($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $data = [
            'contact' => $contact,
        ];

        return view('people.gifts.add', $data);
    }

    /**
     * Actually store the gift.
     * @param  Request $request
     * @param  int $peopleId
     * @return
     */
    public function storeGift(Request $request, $contactId)
    {
        $contact = Contact::findOrFail($contactId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $title = $request->input('title');
        $url = $request->input('url');
        $value = $request->input('value');
        $comment = $request->input('comment');
        $giftOffered = $request->input('gift-offered');

        $gift = new Gift;
        $gift->contact_id = $contact->id;
        $gift->account_id = $contact->account_id;
        $gift->name = $title;

        if ($url != '') {
            $gift->url = $url;
        }

        if ($value !== '') {
            $gift->value_in_dollars = $value;
        }

        if ($comment != '') {
            $gift->comment = $comment;
        }

        if ($giftOffered == 'is_an_idea') {
            $gift->is_an_idea = 'true';
            $gift->has_been_offered = 'false';
        } else {
            $gift->is_an_idea = 'false';
            $gift->has_been_offered = 'true';
        }

        // is this gift for someone in particular?
        $giftForSomeone = $request->input('giftForSomeone');
        if ($giftForSomeone != null) {
            $lovedOne = $request->input('lovedOne');
            $type = substr($lovedOne, 0, 1);

            if ($type == 'K') {
                $objectType = 'kid';
                $objectId = substr($lovedOne, 1);
            } else {
                $objectType = 'significantOther';
                $objectId = substr($lovedOne, 1);
            }

            $gift->about_object_id = $objectId;
            $gift->about_object_type = $objectType;
        }

        $gift->save();

        $contact->logEvent('gift', $gift->id, 'create');

        // increment counter
        if ($gift->is_an_idea == 'true') {
            $contact->number_of_gifts_ideas = $contact->number_of_gifts_ideas + 1;
        } else {
            $contact->number_of_gifts_offered = $contact->number_of_gifts_offered + 1;
        }

        $contact->save();

        $request->session()->flash('success', trans('people.gifts_add_success'));

        return redirect('/people/' . $contact->id);
    }

    /**
     * Mark the gift as deleted.
     * @param  Request $request
     * @param  int $peopleId
     * @param  int $reminderId
     */
    public function deleteGift(Request $request, $contactId, $giftId)
    {
        $contact = Contact::findOrFail($contactId);
        $gift = Gift::findOrFail($giftId);

        if ($contact->account_id !== Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        if ($gift->contact_id !== $contact->id) {
            return redirect()->route('people.index');
        }

        // Delete all events
        $events = Event::where('contact_id', $gift->contact_id)
                          ->where('account_id', $gift->account_id)
                          ->where('object_type', 'gift')
                          ->where('object_id', $gift->id)
                          ->get();

        foreach ($events as $event) {
            $event->delete();
        }

        // Decrease number of gifts
        if ($gift->is_an_idea == 'true') {
            $contact->number_of_gifts_ideas = $contact->number_of_gifts_ideas - 1;

            if ($contact->number_of_gifts_ideas < 1) {
                $contact->number_of_gifts_ideas = 0;
            }
        } else {
            $contact->number_of_gifts_offered = $contact->number_of_gifts_offered - 1;

            if ($contact->number_of_gifts_offered < 1) {
                $contact->number_of_gifts_offered = 0;
            }
        }
        $contact->save();

        $gift->delete();

        $request->session()->flash('success', trans('people.gifts_delete_success'));

        return redirect('/people/' . $contact->id);
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
