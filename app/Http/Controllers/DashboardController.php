<?php

namespace App\Http\Controllers;

use App\Models\User\User;
use App\Helpers\DateHelper;
use App\Models\Contact\Debt;
use Illuminate\Http\Request;
use App\Helpers\AccountHelper;
use function Safe\json_encode;
use App\Helpers\InstanceHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Debt\Debt as DebtResource;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $account = auth()->user()->account()
            ->withCount(
                'contacts', 'reminders', 'notes', 'activities', 'gifts', 'tasks'
            )->with('debts.contact')
            ->first();

        if ($account->contacts()->real()->active()->count() === 0) {
            return view('dashboard.blank');
        }

        // Fetch last updated contacts
        $lastUpdatedContactsCollection = collect([]);
        $lastUpdatedContacts = $account->contacts()
            ->real()
            ->active()
            ->alive()
            ->latest('last_consulted_at')
            ->limit(10)
            ->get();
        foreach ($lastUpdatedContacts as $contact) {
            $data = [
                'id' => $contact->hashID(),
                'has_avatar' => $contact->has_avatar,
                'avatar_url' => $contact->getAvatarURL(),
                'initials' => $contact->getInitials(),
                'default_avatar_color' => $contact->default_avatar_color,
                'complete_name' => $contact->name,
            ];
            $lastUpdatedContactsCollection->push(json_encode($data));
        }

        $debt = $account->debts->where('status', 'inprogress');

        $debt_due = $debt->where('in_debt', 'yes')
            ->reduce(function ($totalDueDebt, Debt $debt) {
                return $totalDueDebt + $debt->amount;
            }, 0);

        $debt_owed = $debt->where('in_debt', 'no')
            ->reduce(function ($totalOwedDebt, Debt $debt) {
                return $totalOwedDebt + $debt->amount;
            }, 0);

        // get last 3 changelog entries
        $changelogs = InstanceHelper::getChangelogEntries(3);

        // Load the reminderOutboxes for the upcoming three months
        $reminderOutboxes = [
            0 => AccountHelper::getUpcomingRemindersForMonth(auth()->user()->account, 0),
            1 => AccountHelper::getUpcomingRemindersForMonth(auth()->user()->account, 1),
            2 => AccountHelper::getUpcomingRemindersForMonth(auth()->user()->account, 2),
        ];

        $data = [
            'lastUpdatedContacts' => $lastUpdatedContactsCollection,
            'number_of_contacts' => $account->contacts()->real()->active()->count(),
            'number_of_reminders' => $account->reminders_count,
            'number_of_notes' => $account->notes_count,
            'number_of_activities' => $account->activities_count,
            'number_of_gifts' => $account->gifts_count,
            'number_of_tasks' => $account->tasks_count,
            'debt_due' => $debt_due,
            'debt_owed' => $debt_owed,
            'debts' => $debt,
            'user' => auth()->user(),
            'changelogs' => $changelogs,
            'reminderOutboxes' => $reminderOutboxes,
        ];

        return view('dashboard.index', $data);
    }

    /**
     * Get calls for the dashboard.
     * @return Collection
     */
    public function calls()
    {
        $callsCollection = collect([]);
        $calls = auth()->user()->account->calls()
            ->get()
            ->reject(function ($call) {
                return is_null($call->contact);
            })
            ->take(15);

        foreach ($calls as $call) {
            $data = [
                'id' => $call->id,
                'called_at' => DateHelper::getShortDate($call->called_at),
                'name' => $call->contact->getIncompleteName(),
                'contact_id' => $call->contact->hashID(),
            ];
            $callsCollection->push($data);
        }

        return $callsCollection;
    }

    /**
     * Get notes for the dashboard.
     * @return Collection
     */
    public function notes()
    {
        $notesCollection = collect([]);
        $notes = auth()->user()->account->notes()->favorited()->get();

        foreach ($notes as $note) {
            $data = [
                'id' => $note->id,
                'body' => $note->body,
                'created_at' => DateHelper::getShortDate($note->created_at),
                'name' => $note->contact->getIncompleteName(),
                'contact' => [
                    'id' => $note->contact->hashID(),
                    'has_avatar' => $note->contact->has_avatar,
                    'avatar_url' => $note->contact->getAvatarURL(),
                    'initials' => $note->contact->getInitials(),
                    'default_avatar_color' => $note->contact->default_avatar_color,
                    'complete_name' => $note->contact->name,
                ],
            ];
            $notesCollection->push($data);
        }

        return $notesCollection;
    }

    /**
     * Get debts for the dashboard.
     * @return Collection
     */
    public function debts()
    {
        $debtsCollection = collect([]);
        $debts = auth()->user()->account->debts()->get();

        foreach ($debts as $debt) {
            $debtsCollection->push(new DebtResource($debt));
        }

        return $debtsCollection;
    }

    /**
     * Save the current active tab to the User table.
     */
    public function setTab(Request $request)
    {
        auth()->user()->dashboard_active_tab = $request->input('tab');
        auth()->user()->save();
    }
}
