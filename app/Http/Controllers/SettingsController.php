<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Kid;
use App\Note;
use App\Task;
use App\Debt;
use App\Event;
use App\Entry;
use Validator;
use App\Account;
use App\Contact;
use App\Activity;
use App\Reminder;
use Carbon\Carbon;
use App\Http\Requests;
use App\SignificantOther;
use App\ActivityStatistic;
use Illuminate\Http\Request;
use App\Helpers\RandomHelper;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('settings.index');
    }

    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
          'email' => 'required|email|max:2083',
        ]);

        if ($validator->fails()) {
            return redirect('/settings')
              ->withInput()
              ->withErrors($validator);
        }

        $input = $request->only([
            'email',
            'timezone',
            'layout',
            'locale',
            'currency_id'
        ]);

        $user = Auth::user();
        $user->update($input);

        return redirect('settings')->with('status', trans('settings.settings_success'));
    }

    public function delete()
    {
        // get the account id
        $accountID = Auth::user()->account_id;

        // delete all reminders
        Reminder::where('account_id', $accountID)->forceDelete();

        // delete contacts
        Contact::where('account_id', $accountID)->forceDelete();

        // delete kids
        Kid::where('account_id', $accountID)->forceDelete();

        // delete notes
        Note::where('account_id', $accountID)->forceDelete();

        // delete significant others
        SignificantOther::where('account_id', $accountID)->forceDelete();

        // delete tasks
        Task::where('account_id', $accountID)->forceDelete();

        // delete activities
        Activity::where('account_id', $accountID)->forceDelete();

        // delete events
        Event::where('account_id', $accountID)->forceDelete();

        // delete account
        Account::find($accountID)->delete();

        Auth::user()->delete();

        return redirect('/');
    }

    /**
     * Display the Export view
     */
    public function export()
    {
        return view('settings.export');
    }

    /**
     * Exports the data of the account in SQL format
     * @return Response
     */
    public function exportToSql()
    {
        $filename = rand().'.sql';
        $path = 'sql/';
        $fullPath = $path.$filename;

        $sql = "# ************************************************************
# ".Auth::user()->first_name." ".Auth::user()->last_name." dump of data
# {$filename}
# Export date: ".Carbon::now()."
# ************************************************************

".PHP_EOL;

        $ignoredTables = [
            'activity_type_groups',
            'activity_types',
            'cache',
            'countries',
            'currencies',
            'failed_jobs',
            'jobs',
            'migrations',
            'password_resets',
            'sessions',
            'statistics',
            'accounts' // this will have a special treatment below
        ];

        $user = Auth::user();
        $account = $user->account;

        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema="monica"');

        // Looping over the tables
        foreach ($tables as $table) {
            $tableName = $table->table_name;

            if (in_array($tableName, $ignoredTables)) {
                continue;
            }

            $tableData = DB::table($tableName)->get();

            // Looping over the rows
            foreach ($tableData as $data) {

                $newSQLLine = 'INSERT INTO '.$tableName.' (';
                $tableValues = [];
                $skipLine = false;

                // Looping over the column names
                $tableColumnNames = [];
                foreach ($data as $columnName => $value) {
                    array_push($tableColumnNames, $columnName);
                }

                $newSQLLine .= implode(',', $tableColumnNames).') VALUES (';

                // Looping over the values
                foreach ($data as $columnName => $value) {

                    if ($columnName == 'account_id') {
                        if ($value !== $account->id) {
                            $skipLine = true;
                            break;
                        }
                    }

                    if (is_null($value)) {
                        $value = 'NULL';
                    } elseif (!is_numeric($value)) {
                        $value = "'".addslashes($value)."'";
                    }

                    array_push($tableValues, $value);
                }

                if ($skipLine == false) {
                    $newSQLLine .= implode(',', $tableValues).');'.PHP_EOL;
                    $sql .= $newSQLLine;
                }
            }
        }

        // Specific to `accounts` table
        // TODO: simplify this
        foreach ($tables as $table) {
            $tableName = $table->table_name;

            if ($tableName !== 'accounts') {
                continue;
            }

            $tableData = DB::table($tableName)->get();

            foreach ($tableData as $data) {

                $newSQLLine = 'INSERT INTO '.$tableName.' VALUES (';
                $tableValues = [];
                $skipLine = false;

                foreach ($data as $columnName => $value) {

                    if ($columnName == 'id') {
                        if ($value !== $account->id) {
                            $skipLine = true;
                            break;
                        }
                    }

                    if (is_null($value)) {
                        $value = 'NULL';
                    } elseif (!is_numeric($value)) {
                        $value = "'".addslashes($value)."'";
                    }

                    array_push($tableValues, $value);
                }

                if ($skipLine == false) {
                    $newSQLLine .= implode(',', $tableValues).');'.PHP_EOL;
                    $sql .= $newSQLLine;
                }
            }
        }

        Storage::disk('public')->put($fullPath, $sql);

        return response()->download(Storage::disk('public')->getDriver()->getAdapter()->getPathPrefix().$fullPath, 'monica.sql')->deleteFileAfterSend(true);
    }
}
