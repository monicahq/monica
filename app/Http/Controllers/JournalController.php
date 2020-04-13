<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\Journal\Day;
use Illuminate\Http\Request;
use App\Models\Journal\Entry;
use App\Helpers\JournalHelper;
use App\Models\Journal\JournalEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Journal\DaysRequest;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        return view('journal.index');
    }

    /**
     * Get all the journal entries.
     * @return array
     */
    public function list()
    {
        $entries = collect([]);
        $journalEntries = auth()->user()->account->journalEntries()->paginate(30);

        // this is needed to determine if we need to display the calendar
        // (month + year) next to the journal entry
        $previousEntryMonth = 0;
        $previousEntryYear = 0;
        $showCalendar = true;

        foreach ($journalEntries->items() as $journalEntry) {
            if ($previousEntryMonth == $journalEntry->date->month && $previousEntryYear == $journalEntry->date->year) {
                $showCalendar = false;
            }

            $data = [
                'id' => $journalEntry->id,
                'date' => $journalEntry->date,
                'journalable_id' => $journalEntry->journalable_id,
                'journalable_type' => $journalEntry->journalable_type,
                'object' => $journalEntry->getObjectData(),
                'show_calendar' => $showCalendar,
            ];
            $entries->push($data);

            $previousEntryMonth = $journalEntry->date->month;
            $previousEntryYear = $journalEntry->date->year;
            $showCalendar = true;
        }

        // I need the pagination items when I send back the array.
        // There is probably a simpler way to achieve this.
        return [
            'total' => $journalEntries->total(),
            'per_page' => $journalEntries->perPage(),
            'current_page' => $journalEntries->currentPage(),
            'next_page_url' => $journalEntries->nextPageUrl(),
            'prev_page_url' => $journalEntries->previousPageUrl(),
            'data' => $entries,
        ];
    }

    /**
     * Gets the details of a single Journal Entry.
     * @param  JournalEntry $journalEntry
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
     * @param  Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
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

        $entry = new Entry;
        $entry->account_id = $request->user()->account_id;
        $entry->post = $request->input('entry');

        if ($request->input('title') != '') {
            $entry->title = $request->input('title');
        }

        $entry->save();

        $entry->date = $request->input('date');
        // Log a journal entry
        JournalEntry::add($entry);

        return redirect()->route('journal.index');
    }

    /**
     * Display the Edit journal entry screen.
     *
     * @param Entry $entry
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
     * @param  Request $request
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
