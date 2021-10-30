<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\Journal\Day;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Journal\Entry;
use App\Helpers\JournalHelper;
use App\Models\Journal\JournalEntry;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Journal\DaysRequest;
use App\Services\Journal\CreateJournalEntry;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $entries = $this->list();

        return view('journal.index')->withEntries($entries);
    }

    /**
     * Get all the journal entries.
     *
     * @return array
     */
    public function list()
    {
        $journalEntries = auth()->user()->account->journalEntries()->get();
        $entriesCollection = collect([]);

        foreach ($journalEntries as $journalEntry) {
            $data = [
                'id' => $journalEntry->id,
                'written_at' => DateHelper::getShortDate($journalEntry->written_at),
                'title' => $journalEntry->title,
                'post' => Str::limit($journalEntry->post, 20),
            ];
            $entriesCollection->push($data);
        }

        return $entriesCollection;
    }

    /**
     * Gets the details of a single Journal Entry.
     *
     * @param  JournalEntry  $journalEntry
     * @return array
     */
    public function get(JournalEntry $journalEntry)
    {
        return $journalEntry->getObjectData();
    }

    /**
     * Store the day entry.
     */
    public function storeDay(DaysRequest $request)
    {
        $day = auth()->user()->account->days()->create([
            'date' => now(DateHelper::getTimezone()),
            'rate' => $request->input('rate'),
            'comment' => $request->input('comment'),
        ]);

        // Log a journal entry
        $journalEntry = JournalEntry::add($day);

        return [
            'id' => $journalEntry->id,
            'date' => $journalEntry->date,
            'journalable_id' => $journalEntry->journalable_id,
            'journalable_type' => $journalEntry->journalable_type,
            'object' => $journalEntry->getObjectData(),
            'show_calendar' => true,
        ];
    }

    /**
     * Delete the Day entry.
     *
     * @return void
     */
    public function trashDay(Day $day): void
    {
        $day->deleteJournalEntry();
        $day->delete();
    }

    /**
     * Indicates whether the user has already rated the current day.
     *
     * @return string
     */
    public function hasRated()
    {
        if (JournalHelper::hasAlreadyRatedToday(auth()->user())) {
            return 'true';
        }

        return 'notYet';
    }

    /**
     * Display the Create journal entry screen.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function create()
    {
        return view('journal.add');
    }

    /**
     * Saves the journal entry.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'post' => 'required|string',
            'title' => 'nullable|string',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        app(CreateJournalEntry::class)->execute([
            'account_id' => $user->account_id,
            'title' => $request->input('title'),
            'post' => $request->input('post'),
            'date' => $request->input('date'),
        ]);

        return response()->json([
            'data' => route('journal.index'),
        ], 201);
    }

    /**
     * Display the Edit journal entry screen.
     *
     * @param  Entry  $entry
     * @return \Illuminate\View\View
     */
    public function edit(Entry $entry)
    {
        return view('journal.edit')
            ->withEntry($entry);
    }

    /**
     * Update a journal entry.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Entry $entry)
    {
        $validator = Validator::make($request->all(), [
            'entry' => 'required|string',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        $entry->post = $request->input('entry');

        if ($request->input('title') != '') {
            $entry->title = $request->input('title');
        }

        $entry->save();

        // Update journal entry
        $journalEntry = $entry->journalEntry;
        if ($journalEntry) {
            $entry->date = $request->input('date');
            $journalEntry->edit($entry);
        }

        return redirect()->route('journal.index');
    }

    /**
     * Delete the reminder.
     */
    public function deleteEntry(Request $request, Entry $entry)
    {
        $entry->deleteJournalEntry();
        $entry->delete();

        return ['true'];
    }
}
