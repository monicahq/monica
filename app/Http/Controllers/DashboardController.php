<?php

namespace App\Http\Controllers;

use App\Debt;
use App\Note;
use App\User;
use App\Contact;
use Illuminate\Http\Request;
use App\Helpers\CouchDbHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Debt\Debt as DebtResource;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $account = Auth::user()->account()
            ->withCount(
                'contacts', 'reminders', 'notes', 'activities', 'gifts', 'tasks'
            )->with('debts.contact')
            ->first();

        if ($account->contacts()->count() === 0) {
            return view('dashboard.blank');
        }

        // Fetch last updated contacts
        $lastUpdatedContactsCollection = collect([]);
        $lastUpdatedContacts = $account->contacts()->where('is_partial', false)->latest('updated_at')->limit(10)->get();
        foreach ($lastUpdatedContacts as $contact) {
            $data = [
                'id' => $contact->hashID(),
                'has_avatar' => $contact->has_avatar,
                'avatar_url' => $contact->getAvatarURL(110),
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

        $data = [
            'lastUpdatedContacts' => $lastUpdatedContactsCollection,
            'number_of_contacts' => $account->contacts()->real()->count(),
            'number_of_reminders' => $account->reminders_count,
            'number_of_notes' => $account->notes_count,
            'number_of_activities' => $account->activities_count,
            'number_of_gifts' => $account->gifts_count,
            'number_of_tasks' => $account->tasks_count,
            'debt_due' => $debt_due,
            'debt_owed' => $debt_owed,
            'debts' => $debt,
            'user' => auth()->user(),
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
        $calls = auth()->user()->account->calls()->limit(15)->get();

        foreach ($calls as $call) {
            $data = [
                'id' => $call->id,
                'called_at' => \App\Helpers\DateHelper::getShortDate($call->called_at),
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
        $client = CouchDbHelper::getAccountDatabase(auth()->user()->account->id);

        $notesCollection = collect([]);
        $response = $client->descending(true)->include_docs(true)->getView('notes', 'favorites');
        $notes = $response->rows;

        foreach ($notes as $noteArray) {
            $note = new Note((array) $noteArray->doc);
            $data = [
                '_id' => $note->_id,
                'body' => $note->body,
                'contact_id' => $note->contact_id,
                'created_at' => \App\Helpers\DateHelper::getShortDate($note->created_at),
            ];
            $notesCollection->push($data);
        }

        $contactIds = $notesCollection
            ->map(function ($note) {
                return $note['contact_id'];
            })
            ->unique();
        $contacts = DB::table('contacts')->whereIn('id', $contactIds)->get();

        $notesCollection = $notesCollection->map(function ($note) use ($contacts) {
            $contact = new Contact((array) $contacts->firstWhere('id', $note['contact_id']));
            $note['name'] = $contact->getIncompleteName();
            $note['contact'] = [
                'id' => $contact->hashID(),
                'has_avatar' => $contact->has_avatar,
                'avatar_url' => $contact->getAvatarURL(110),
                'initials' => $contact->getInitials(),
                'default_avatar_color' => $contact->default_avatar_color,
                'complete_name' => $contact->name,
            ];

            return $note;
        });

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
        auth()->user()->dashboard_active_tab = $request->get('tab');
        auth()->user()->save();
    }
}
