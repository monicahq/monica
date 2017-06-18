<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ExportAccountAsSQL;
use App\Http\Requests\SettingsRequest;
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

    /**
     * Save user settings.
     *
     * @param SettingsRequest $request
     * @return \Illuminate\Http\Response
     */
    public function save(SettingsRequest $request)
    {
        $request->user()->update(
            $request->only([
                'email',
                'timezone',
                'locale',
                'currency_id',
            ]) + [
                'fluid_container' => $request->get('layout')
            ]
        );

        return redirect('settings')
            ->with('status', trans('settings.settings_success'));
    }

    /**
     * Delete user account
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $user = $request->user();
        $account = $user->account;

        if($account) {
            $account->reminders->each->forceDelete();
            $account->kids->each->forceDelete();
            $account->notes->each->forceDelete();
            $account->significantOthers->each->forceDelete();
            $account->tasks->each->forceDelete();
            $account->activities->each->forceDelete();
            $account->events->each->forceDelete();
            $account->contacts->each->forceDelete();
            $account->forceDelete();
        }

        auth()->logout();
        $user->forceDelete();

        return redirect('/');
    }

    /**
     * Display the export view
     *
     * @return \Illuminate\Http\Response
     */
    public function export()
    {
        return view('settings.export');
    }

    /**
     * Exports the data of the account in SQL format
     *
     * @return \Illuminate\Http\Response
     */
    public function exportToSql()
    {
        $path = $this->dispatchNow(new ExportAccountAsSQL());

        return response()
            ->download(Storage::disk('public')->getDriver()->getAdapter()->getPathPrefix() . $path, 'monica.sql')
            ->deleteFileAfterSend(true);
    }
}
