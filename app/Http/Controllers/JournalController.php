<?php

namespace App\Http\Controllers;

use Auth;
use App\Entry;
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
        $entries = Entry::where('account_id', Auth::user()->account_id)
                      ->orderBy('created_at', 'desc')
                      ->get();

        $data = [
            'entries' => $entries,
        ];

        return view('journal.index', $data);
    }

    public function add()
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
