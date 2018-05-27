<?php

namespace App\Http\Controllers;

use App\Tag;
use App\User;
use App\ImportJob;
use App\Invitation;
use Illuminate\Http\Request;
use App\Jobs\SendNewUserAlert;
use App\Jobs\ExportAccountAsSQL;
use App\Jobs\AddContactFromVCard;
use App\Jobs\SendInvitationEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ImportsRequest;
use App\Http\Requests\SettingsRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\InvitationRequest;
use PragmaRX\Google2FALaravel\Google2FA;

class SettingsController extends Controller
{
    protected $ignoredTables = [
        'accounts',
        'activity_type_groups',
        'activity_types',
        'api_usage',
        'cache',
        'changelog_user',
        'changelogs',
        'countries',
        'currencies',
        'default_contact_field_types',
        'default_contact_modules',
        'default_relationship_type_groups',
        'default_relationship_types',
        'failed_jobs',
        'instances',
        'jobs',
        'migrations',
        'oauth_access_tokens',
        'oauth_auth_codes',
        'oauth_clients',
        'oauth_personal_access_clients',
        'oauth_refresh_tokens',
        'password_resets',
        'pet_categories',
        'sessions',
        'statistics',
        'subscriptions',
        'users',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('settings.index')
                ->withLocales(\App\Helpers\LocaleHelper::getLocaleList())
                ->withHours(\App\Helpers\DateHelper::getListOfHours());
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
                'first_name',
                'last_name',
                'email',
                'timezone',
                'locale',
                'currency_id',
                'name_order',
            ]) + [
                'fluid_container' => $request->get('layout'),
            ]
        );

        $request->user()->account->default_time_reminder_is_sent = $request->get('reminder_time');
        $request->user()->account->save();

        return redirect('settings')
            ->with('status', trans('settings.settings_success', [], $request['locale']));
    }

    /**
     * Delete user account.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $user = $request->user();
        $account = $user->account;

        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema="monica"');

        // Looping over the tables
        foreach ($tables as $table) {
            $tableName = $table->table_name;

            if (in_array($tableName, $this->ignoredTables)) {
                continue;
            }

            DB::table($tableName)->where('account_id', $account->id)->delete();
        }

        DB::table('accounts')->where('id', $account->id)->delete();

        $account = auth()->user()->account;

        if ($account->isSubscribed() && auth()->user()->has_access_to_paid_version_for_free == 0) {
            $account->subscription($account->getSubscribedPlanName())->cancelNow();
        }

        auth()->logout();
        $user->forceDelete();

        return redirect('/');
    }

    /**
     * Reset user account.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function reset(Request $request)
    {
        $user = $request->user();
        $account = $user->account;

        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema="monica"');

        // Looping over the tables
        foreach ($tables as $table) {
            $tableName = $table->table_name;

            if (in_array($tableName, $this->ignoredTables)) {
                continue;
            }

            DB::table($tableName)->where('account_id', $account->id)->delete();
        }

        $account->populateDefaultFields($account);

        return redirect('/settings')
                    ->with('status', trans('settings.reset_success'));
    }

    /**
     * Display the export view.
     *
     * @return \Illuminate\Http\Response
     */
    public function export()
    {
        return view('settings.export');
    }

    /**
     * Exports the data of the account in SQL format.
     *
     * @return \Illuminate\Http\Response
     */
    public function exportToSql()
    {
        $path = $this->dispatchNow(new ExportAccountAsSQL());

        return response()
            ->download(Storage::disk('public')->getDriver()->getAdapter()->getPathPrefix().$path, 'monica.sql')
            ->deleteFileAfterSend(true);
    }

    /**
     * Display the import view.
     *
     * @return \Illuminate\Http\Response
     */
    public function import()
    {
        if (auth()->user()->account->importjobs->count() == 0) {
            return view('settings.imports.blank');
        }

        return view('settings.imports.index');
    }

    /**
     * Display the Import people's view.
     *
     * @return \Illuminate\Http\Response
     */
    public function upload()
    {
        if (config('monica.requires_subscription') && ! auth()->user()->account->isSubscribed()) {
            return redirect('/settings/subscriptions');
        }

        return view('settings.imports.upload');
    }

    public function storeImport(ImportsRequest $request)
    {
        $filename = $request->file('vcard')->store('imports', 'public');

        $importJob = auth()->user()->account->importjobs()->create([
            'user_id' => auth()->user()->id,
            'type' => 'vcard',
            'filename' => $filename,
        ]);

        dispatch(new AddContactFromVCard($importJob, $request->get('behaviour')));

        return redirect()->route('settings.import');
    }

    /**
     * Display the import report view.
     *
     * @return \Illuminate\Http\Response
     */
    public function report($importJobId)
    {
        $importJob = ImportJob::findOrFail($importJobId);

        if ($importJob->account_id != auth()->user()->account->id) {
            return redirect()->route('settings.index');
        }

        return view('settings.imports.report', compact('importJob'));
    }

    /**
     * Display the users view.
     *
     * @return \Illuminate\Http\Response
     */
    public function users()
    {
        $users = auth()->user()->account->users;

        if ($users->count() == 1 && auth()->user()->account->invitations()->count() == 0) {
            return view('settings.users.blank');
        }

        return view('settings.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function addUser()
    {
        if (config('monica.requires_subscription') && ! auth()->user()->account->isSubscribed()) {
            return redirect('/settings/subscriptions');
        }

        return view('settings.users.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param InvitationRequest $request
     * @return \Illuminate\Http\Response
     */
    public function inviteUser(InvitationRequest $request)
    {
        // Make sure the confirmation to invite has not been bypassed
        if (! $request->get('confirmation')) {
            return redirect()->back()->withErrors(trans('settings.users_error_please_confirm'))->withInput();
        }

        // Is the email address already taken?
        $users = User::where('email', $request->only(['email']))->count();
        if ($users > 0) {
            return redirect()->back()->withErrors(trans('settings.users_error_email_already_taken'))->withInput();
        }

        // Has this user been invited already?
        $invitations = Invitation::where('email', $request->only(['email']))->count();
        if ($invitations > 0) {
            return redirect()->back()->withErrors(trans('settings.users_error_already_invited'))->withInput();
        }

        $invitation = auth()->user()->account->invitations()->create(
            $request->only([
                'email',
            ])
            + [
                'invited_by_user_id' => auth()->user()->id,
                'account_id' => auth()->user()->account_id,
                'invitation_key' => str_random(100),
            ]
        );

        dispatch(new SendInvitationEmail($invitation));

        auth()->user()->account->update([
            'number_of_invitations_sent' => auth()->user()->account->number_of_invitations_sent + 1,
        ]);

        return redirect('settings/users')
            ->with('status', trans('settings.settings_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Invitation $invitation
     * @return \Illuminate\Http\Response
     */
    public function destroyInvitation(Invitation $invitation)
    {
        $invitation->delete();

        return redirect('/settings/users')
            ->with('success', trans('settings.users_invitation_deleted_confirmation_message'));
    }

    /**
     * Display the specified resource.
     *
     * @param string $key
     * @return \Illuminate\Http\Response
     */
    public function acceptInvitation($key)
    {
        if (Auth::check()) {
            return redirect('/');
        }

        Invitation::where('invitation_key', $key)
            ->firstOrFail();

        return view('settings.users.accept', compact('key'));
    }

    /**
     * Store the specified resource.
     *
     * @param Request $request
     * @param string $key
     * @return \Illuminate\Http\Response
     */
    public function storeAcceptedInvitation(Request $request, $key)
    {
        $invitation = Invitation::where('invitation_key', $key)
                                    ->firstOrFail();

        // as a security measure, make sure that the new user provides the email
        // of the person who has invited him/her.
        if ($request->input('email_security') != $invitation->invitedBy->email) {
            return redirect()->back()->withErrors(trans('settings.users_error_email_not_similar'))->withInput();
        }

        $user = User::createDefault($invitation->account_id,
                    $request->input('first_name'),
                    $request->input('last_name'),
                    $request->input('email'),
                    $request->input('password'));

        $invitation->delete();

        // send me an alert
        dispatch(new SendNewUserAlert($user));

        if (Auth::attempt(['email' => $user->email, 'password' => $request->input('password')])) {
            return redirect('dashboard');
        }
    }

    /**
     * Delete additional user account.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function deleteAdditionalUser(Request $request, $userID)
    {
        $user = User::find($userID);

        if ($user->account_id != auth()->user()->account_id) {
            return redirect('/');
        }

        // make sure you don't delete yourself from this screen
        if ($user->id == auth()->user()->id) {
            return redirect('/');
        }

        $user = User::find($userID);
        $user->delete();

        return redirect('/settings/users')
                ->with('success', trans('settings.users_list_delete_success'));
    }

    /**
     * Display the list of tags for this account.
     */
    public function tags()
    {
        return view('settings.tags');
    }

    public function deleteTag(Request $request, $tagId)
    {
        $tag = Tag::findOrFail($tagId);

        if ($tag->account_id != auth()->user()->account_id) {
            return redirect('/');
        }

        $tag->contacts()->detach();

        $tag->delete();

        return redirect('/settings/tags')
                ->with('success', trans('settings.tags_list_delete_success'));
    }

    public function api()
    {
        return view('settings.api.index');
    }

    public function security(Request $request)
    {
        return view('settings.security.index', ['is2FAActivated' => app('pragmarx.google2fa')->isActivated()]);
    }
}
