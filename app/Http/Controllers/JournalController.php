<?php

namespace App\Http\Controllers;

use Auth;
use App\Entry;
use App\JournalEntry;
use Validator;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $journalEntries = auth()->user()->account->journalEntries()->limit(1)->get();

        $entries = Entry::where('account_id', Auth::user()->account_id)
                      ->orderBy('created_at', 'desc')
                      ->get();

        $data = [
            'entries' => $entries,
            'journalEntries' => $journalEntries,
        ];

        return view('journal.index', $data);
    }

    /**
     * Get all the journal entries.
     * @return array
     */
    public function list()
    {
        $entries = collect([]);
        $journalEntries = auth()->user()->account->journalEntries()->get();

        // this is needed to determine if we need to display the calendar
        // (month + year) next to the journal entry
        $previousEntryMonth = 0;
        $showCalendar = true;

        foreach ($journalEntries as $journalEntry) {
            if ($previousEntryMonth == $journalEntry->date->month)
            {
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
            $showCalendar = true;
        }

        return $entries;
    }

    /**
     * Gets the details of a single Journal Entry.
     * @param  JournalEntry $journalEntry
     * @return array
     */
    public function get(JournalEntry $journalEntry)
    {
        $object = $journalEntry->getObjectData();

        return $object;
    }

    /**
     * Display the Create journal entry screen.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('journal.add');
    }

    /**
     * Saves the journal entry.
     *
     * @param  Request $request
     * @return Response
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entry' => 'required',
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        $entry = new Entry;
        $entry->account_id = Auth::user()->account_id;
        $entry->post = $request->input('entry');

        if ($request->input('title') != '') {
            $entry->title = $request->input('title');
        }

        $entry->save();

        return redirect()->route('journal.index');
    }

    /**
     * Delete the reminder.
     */
    public function deleteEntry(Request $request, $entryId)
    {
        $entry = Entry::findOrFail($entryId);

        if ($entry->account_id != Auth::user()->account_id) {
            return redirect()->route('people.index');
        }

        $entry->delete();
        $request->session()->flash('success', trans('journal.entry_delete_success'));

        return redirect('/journal');
    }
}
