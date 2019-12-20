<?php

namespace App\Http\Controllers;

use App\Helpers\DBHelper;
use App\Models\User\User;
use App\Helpers\DateHelper;
use App\Models\Contact\Tag;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\LocaleHelper;
use App\Helpers\TimezoneHelper;
use App\Jobs\ExportAccountAsSQL;
use App\Jobs\AddContactFromVCard;
use App\Models\Account\ImportJob;
use App\Models\Account\Invitation;
use App\Services\User\EmailChange;
use Illuminate\Support\Facades\DB;
use App\Exceptions\StripeException;
use Lahaxearnaud\U2f\Models\U2fKey;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ImportsRequest;
use App\Notifications\InvitationMail;
use App\Http\Requests\SettingsRequest;
use Illuminate\Support\Facades\Storage;
use LaravelWebauthn\Models\WebauthnKey;
use App\Http\Requests\InvitationRequest;
use App\Services\Contact\Tag\DestroyTag;
use App\Services\Account\Settings\DestroyAllDocuments;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use App\Http\Resources\Settings\U2fKey\U2fKey as U2fKeyResource;
use App\Http\Resources\Settings\WebauthnKey\WebauthnKey as WebauthnKeyResource;
use App\Services\Account\Settings\DestroyAccount;
use App\Services\Account\Settings\ResetAccount;

class SettingsController
{
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
                'fluid_container' => $request->input('layout'),
                'temperature_scale' => $request->input('temperature_scale'),
            ]
        );

        if ($user->email != $request->input('email')) {
            app(EmailChange::class)->execute([
                'account_id' => $user->account_id,
                'email' => $request->input('email'),
                'user_id' => $user->id,
            ]);
        }

        $user->account->default_time_reminder_is_sent = $request->input('reminder_time');
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
        $account = auth()->user()->account;

        try {
            app(DestroyAccount::class)->execute([
                'account_id' => $account->id,
            ]);
        } catch (StripeException $e) {
            return redirect()->route('settings.index')
                ->withErrors($e->getMessage());
        }

        auth()->logout();

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

        app(ResetAccount::class)->execute([
            'account_id' => $account->id,
        ]);

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

        $adapter = disk_adapter(ExportAccountAsSQL::STORAGE);

        return response()
            ->download($adapter->getPathPrefix().$path, 'monica.sql')
            ->deleteFileAfterSend(true);
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

        dispatch(new AddContactFromVCard($importJob, $request->input('behaviour')));

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
        if (! $request->input('confirmation')) {
            return redirect()->back()->withErrors(trans('settings.users_error_please_confirm'))->withInput();
        }

        // Is the email address already taken?
        $users = User::where('email', $request->only(['email']))->count();
        if ($users > 0) {
            return redirect()->back()->withErrors(trans('settings.users_error_email_already_taken'))->withInput();
        }

        // Has this user already been invited?
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
                'invitation_key' => Str::random(100),
            ]
        );

        $invitation->notify((new InvitationMail())->locale(auth()->user()->locale));

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
        $davroute = route('sabre.dav');
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

        $webauthnKeys = WebauthnKey::where('user_id', auth()->id())->get();

        return view('settings.security.index')
            ->with('is2FAActivated', Google2FA::isActivated())
            ->with('currentkeys', U2fKeyResource::collection($u2fKeys))
            ->withWebauthnKeys(WebauthnKeyResource::collection($webauthnKeys));
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
        /** @var string */
        $view = $request->input('name');

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
