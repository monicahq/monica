<?php

namespace App\Http\Controllers;

use App\Helpers\DBHelper;
use App\Models\User\User;
use App\Helpers\DateHelper;
use App\Models\Contact\Tag;
use Illuminate\Http\Request;
use App\Helpers\LocaleHelper;
use App\Helpers\RequestHelper;
use App\Jobs\SendNewUserAlert;
use App\Helpers\TimezoneHelper;
use App\Jobs\ExportAccountAsSQL;
use App\Jobs\AddContactFromVCard;
use App\Jobs\SendInvitationEmail;
use App\Models\Account\ImportJob;
use App\Models\Account\Invitation;
use App\Services\User\EmailChange;
use Illuminate\Support\Facades\DB;
use Lahaxearnaud\U2f\Models\U2fKey;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ImportsRequest;
use App\Http\Requests\SettingsRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\InvitationRequest;
use App\Services\Contact\Tag\DestroyTag;
use PragmaRX\Google2FALaravel\Google2FA;
use App\Services\Account\DestroyAllDocuments;
use App\Http\Resources\Settings\U2fKey\U2fKey as U2fKeyResource;

class SettingsController
{
    protected $ignoredTables = [
        'accounts',
        'activity_type_activities',
        'activity_types',
        'api_usage',
        'cache',
        'countries',
        'currencies',
        'contact_photo',
        'default_activity_types',
        'default_activity_type_categories',
        'default_contact_field_types',
        'default_contact_modules',
        'default_life_event_categories',
        'default_life_event_types',
        'default_relationship_type_groups',
        'default_relationship_types',
        'emotions',
        'emotions_primary',
        'emotions_secondary',
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
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        'terms',
        'u2f_key',
        'users',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // names order
        $namesOrder = [
            'firstname_lastname',
            'lastname_firstname',
            'firstname_lastname_nickname',
            'firstname_nickname_lastname',
            'lastname_firstname_nickname',
            'lastname_nickname_firstname',
            'nickname',
        ];

        return view('settings.index')
                ->withNamesOrder($namesOrder)
                ->withLocales(LocaleHelper::getLocaleList())
                ->withHours(DateHelper::getListOfHours())
                ->withSelectedTimezone(TimezoneHelper::adjustEquivalentTimezone(DateHelper::getTimezone()))
                ->withTimezones(collect(TimezoneHelper::getListOfTimezones())->map(function ($timezone) {
                    return ['id' => $timezone['timezone'], 'name'=>$timezone['name']];
                }));
    }

    /**
     * Save user settings.
     *
     * @param SettingsRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(SettingsRequest $request)
    {
        $user = $request->user();

        $user->update(
            $request->only([
                'first_name',
                'last_name',
                'timezone',
                'locale',
                'currency_id',
                'name_order',
            ]) + [
                'fluid_container' => $request->get('layout'),
                'temperature_scale' => $request->get('temperature_scale'),
            ]
        );

        if ($user->email != $request->get('email')) {
            app(EmailChange::class)->execute([
                'account_id' => $user->account_id,
                'email' => $request->get('email'),
                'user_id' => $user->id,
            ]);
        }

        $user->account->default_time_reminder_is_sent = $request->get('reminder_time');
        $user->account->save();

        return redirect()->route('settings.index')
            ->with('status', trans('settings.settings_success', [], $request['locale']));
    }

    /**
     * Delete user account.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request)
    {
        $user = $request->user();
        $account = $user->account;

        app(DestroyAllDocuments::class)->execute([
            'account_id' => $account->id,
        ]);

        $tables = DBHelper::getTables();

        // Looping over the tables
        foreach ($tables as $table) {
            $tableName = $table->table_name;

            if (in_array($tableName, $this->ignoredTables)) {
                continue;
            }

            DB::table($tableName)->where('account_id', $account->id)->delete();
        }

        $account = auth()->user()->account;

        if ($account->isSubscribed() && ! $account->has_access_to_paid_version_for_free) {
            $account->subscriptionCancel();
        }

        DB::table('accounts')->where('id', $account->id)->delete();
        auth()->logout();
        $user->delete();

        return redirect()->route('login');
    }

    /**
     * Reset user account.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request)
    {
        $user = $request->user();
        $account = $user->account;

        app(DestroyAllDocuments::class)->execute([
            'account_id' => $account->id,
        ]);

        $tables = DBHelper::getTables();

        // TODO(tom@tomrochette.com): We cannot simply iterate over tables to reset an account
        // as this will not work with foreign key constraints
        // Looping over the tables
        foreach ($tables as $table) {
            $tableName = $table->table_name;

            if (in_array($tableName, $this->ignoredTables)) {
                continue;
            }

            DB::table($tableName)->where('account_id', $account->id)->delete();
        }

        $account->populateDefaultFields();

        return redirect()->route('settings.index')
                    ->with('status', trans('settings.reset_success'));
    }

    /**
     * Display the export view.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function export()
    {
        return view('settings.export');
    }

    /**
     * Exports the data of the account in SQL format.
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response|null
     */
    public function exportToSql()
    {
        $path = dispatch_now(new ExportAccountAsSQL());

        $driver = Storage::disk(ExportAccountAsSQL::STORAGE)->getDriver();
        if ($driver instanceof \League\Flysystem\Filesystem) {
            $adapter = $driver->getAdapter();
            if ($adapter instanceof \League\Flysystem\Adapter\AbstractAdapter) {
                return response()
                    ->download($adapter->getPathPrefix().$path, 'monica.sql')
                    ->deleteFileAfterSend(true);
            }
        }
    }

    /**
     * Display the import view.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
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
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
     */
    public function upload()
    {
        if (config('monica.requires_subscription') && ! auth()->user()->account->isSubscribed()) {
            return redirect()->route('settings.subscriptions.index');
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
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function report($importJobId)
    {
        $importJob = ImportJob::where('account_id', auth()->user()->account_id)
            ->findOrFail($importJobId);

        return view('settings.imports.report', compact('importJob'));
    }

    /**
     * Display the users view.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
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
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
     */
    public function addUser()
    {
        if (config('monica.requires_subscription') && ! auth()->user()->account->isSubscribed()) {
            return redirect()->route('settings.subscriptions.index');
        }

        return view('settings.users.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param InvitationRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
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

        return redirect()->route('settings.users.index')
            ->with('status', trans('settings.settings_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Invitation $invitation
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyInvitation(Invitation $invitation)
    {
        $invitation->delete();

        return redirect()->route('settings.users.index')
            ->with('success', trans('settings.users_invitation_deleted_confirmation_message'));
    }

    /**
     * Display the specified resource.
     *
     * @param string $key
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse
     */
    public function acceptInvitation($key)
    {
        if (Auth::check()) {
            return redirect()->route('login');
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
     *
     * @return null|\Illuminate\Http\RedirectResponse
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
                    $request->input('password'),
                    RequestHelper::ip()
                );
        $user->invited_by_user_id = $invitation->invited_by_user_id;
        $user->save();

        $invitation->delete();

        // send me an alert
        dispatch(new SendNewUserAlert($user));

        if (Auth::attempt(['email' => $user->email, 'password' => $request->input('password')])) {
            return redirect()->route('dashboard.index');
        }
    }

    /**
     * Delete additional user account.
     *
     * @param int $userID
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteAdditionalUser($userID)
    {
        $user = User::where('account_id', auth()->user()->account_id)
            ->findOrFail($userID);

        // make sure you don't delete yourself from this screen
        if ($user->id == auth()->user()->id) {
            return redirect()->route('login');
        }

        $user->delete();

        return redirect()->route('settings.users.index')
                ->with('success', trans('settings.users_list_delete_success'));
    }

    /**
     * Display the list of tags for this account.
     */
    public function tags()
    {
        return view('settings.tags');
    }

    /**
     * Destroy the tag.
     *
     * @param int $tagId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteTag($tagId)
    {
        app(DestroyTag::class)->execute([
            'tag_id' => $tagId,
            'account_id' => auth()->user()->account->id,
        ]);

        return redirect()->route('settings.tags.index')
                ->with('success', trans('settings.tags_list_delete_success'));
    }

    public function api()
    {
        return view('settings.api.index');
    }

    public function dav()
    {
        $davroute = route('dav');
        $email = auth()->user()->email;

        return view('settings.dav.index')
                ->withDavRoute($davroute)
                ->withCardDavRoute("{$davroute}/addressbooks/{$email}/contacts")
                ->withCalDavBirthdaysRoute("{$davroute}/calendars/{$email}/birthdays")
                ->withCalDavTasksRoute("{$davroute}/calendars/{$email}/tasks");
    }

    public function security()
    {
        $u2fKeys = U2fKey::where('user_id', auth()->id())
                        ->get();

        return view('settings.security.index')
            ->with('is2FAActivated', app('pragmarx.google2fa')->isActivated())
            ->with('currentkeys', U2fKeyResource::collection($u2fKeys));
    }

    /**
     * Update the default view when viewing a contact.
     * The default view can be either the life events feed or the general data
     * about the contact (notes, reminders, ...).
     * Possible values: life-events | notes.
     *
     * @param  Request $request
     * @return string
     */
    public function updateDefaultProfileView(Request $request)
    {
        $allowedValues = ['life-events', 'notes', 'photos'];
        $view = $request->get('name');

        if (! in_array($view, $allowedValues)) {
            return 'not allowed';
        }

        auth()->user()->profile_active_tab = $view;

        if ($view == 'life-events') {
            auth()->user()->profile_new_life_event_badge_seen = true;
        }

        auth()->user()->save();

        return $view;
    }
}
