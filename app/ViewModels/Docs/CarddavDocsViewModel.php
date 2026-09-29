<?php

declare(strict_types=1);

namespace App\ViewModels\Docs;

use Carbon\CarbonImmutable;

class CarddavDocsViewModel
{
    public const string TITLE = 'Sync your contacts and calendars';

    public const string LEAD = 'Keep your contacts in the apps you use every day, with CardDAV and CalDAV.';

    public const string OVERVIEW = 'Each Monica vault is an address book on your device. Contact changes sync in both directions. You can also subscribe to recurring dates and reminder deadlines; those are read-only.';

    /** @var list<array{id: string, label: string}> */
    public const array SECTIONS = [
        ['id' => 'overview', 'label' => 'Overview'],
        ['id' => 'setup', 'label' => 'Before you connect'],
        ['id' => 'connect', 'label' => 'Connect your app'],
        ['id' => 'check', 'label' => 'Check your connection'],
        ['id' => 'sync', 'label' => 'What syncs'],
        ['id' => 'changes', 'label' => 'Editing and deleting'],
        ['id' => 'calendars', 'label' => 'Calendars and reminders'],
        ['id' => 'troubleshooting', 'label' => 'Troubleshooting'],
        ['id' => 'disconnect', 'label' => 'Disconnect a device'],
    ];

    public const string DELETE_NOTICE = 'Deleting a contact in a synced address book also deletes it in Monica and on your other synced devices. To stop syncing, disconnect the account instead of deleting its contacts.';

    public const string ACCESS_NOTICE = 'An API key can access every vault in your account. Selecting fewer address books in your app only changes what that app syncs; it does not restrict the key’s access. The key can also be used with the API.';

    /** @var list<string> */
    public const array SETUP = [
        'Open **Settings → Security and access → API keys** in Monica and choose **Create key**.',
        'Name the key after your device, such as **My iPhone**. Under **What will use this key**, choose **Personal script**. This is the current label for a non-assistant connection; it does not change the key’s permissions.',
        'Leave **Let this key read the values of fields marked sensitive** off unless this device needs them. When enabled, supported sensitive contact fields can also be changed by the device. Other apps with access to your device’s contacts may be able to read the downloaded information.',
        'Create the key and copy it before leaving the screen: Monica shows it only once. Use a separate key for each device so you can disconnect one without affecting the others.',
    ];

    /** @var list<string> */
    public const array CHECKS = [
        'Open your contacts app and select the address book named after your Monica vault. Find a contact you already know is in that vault.',
        'Change that contact’s nickname in Monica, then refresh or sync the app. Check that the new nickname appears.',
        'Change the nickname back in the app, sync again, and refresh the contact in Monica. This checks both directions without creating a duplicate.',
    ];

    public const string TIMING = 'Your app controls when it syncs. Changes may need a manual refresh or the next scheduled sync; they are not guaranteed to appear immediately.';

    /** @var list<array{label: string, text: string}> */
    public const array FIELDS = [
        ['label' => 'Names and nickname', 'text' => 'First, middle and last names, prefix, suffix and nickname. Maiden name and name-order preferences stay in Monica.'],
        ['label' => 'Contact photo', 'text' => 'The contact’s uploaded photo. Initials are not exported as a picture.'],
        ['label' => 'Email, phone, website and postal address', 'text' => 'Values from matching fields in the contact’s template, including repeated values. Labels are sent where supported by the app.'],
        ['label' => 'Birthdays and other recurring dates', 'text' => 'Dates from recurring-date fields with a known month. A date with an unknown year is sent without inventing a birth year; how it appears depends on the app.'],
        ['label' => 'Other information in Monica', 'text' => 'Notes, documents, relationships, activity history and other field types are not exported to the address book.'],
    ];

    public const string CONTACTS = 'Only contacts that are neither archived nor names-only appear in an address book. Each vault has its own address book; create, rename or delete vaults in Monica.';

    public const string SENSITIVE = 'Without the sensitive-fields option, Monica does not add sensitive field values to the card or accept changes to those fields. This is not a way to erase information a device already has: properties previously saved on the synced card can still be returned.';

    /** @var list<array{title: string, text: string}> */
    public const array CHANGES = [
        ['title' => 'Create a contact', 'text' => 'Save it into the address book for the intended vault. Monica creates a contact using that vault’s default template. A contact needs a name. Saving into a local, iCloud or Google address book does not add it to Monica.'],
        ['title' => 'Edit contact details', 'text' => 'Supported details are written into the contact’s template fields. Repeated fields can hold several values. If the template only has room for one phone number, the first goes into that field; extra numbers remain on the synced card and can reach other devices without appearing as fields in Monica.'],
        ['title' => 'Keep extra device information', 'text' => 'Monica keeps additional card properties that do not match its fields. The last card saved by a device replaces the previous one. If an app removes a property it does not understand, that property can disappear from other devices too.'],
        ['title' => 'Remove information', 'text' => 'Removing a supported value in your app removes it from the matching Monica field, provided the key can change that field. The contact representing you cannot be deleted from a device. Device changes are recorded in Settings → Logs with the key’s name.'],
        ['title' => 'Resolve a conflict', 'text' => 'When an app sends the version it last read, Monica rejects a save if that version has since changed. Refresh the contact, review the latest details and reapply your edit. Conflict handling depends on the app.'],
    ];

    public const string MOVE_NOTICE = 'Moving a contact between two vault address books creates a new contact in the destination and deletes the source contact. Only the card’s information transfers. Reminders, history and other Monica information stay with the deleted source record.';

    /** @var list<array{title: string, text: string}> */
    public const array CALENDARS = [
        ['title' => 'Dates calendar', 'text' => 'Each vault offers a calendar named “Vault name · dates”. Recurring dates appear as yearly, all-day events named after the contact and the field. When the year is unknown, the recurrence starts in the current year. Dates with an unknown month and fields marked sensitive are excluded, even when the key allows sensitive contact fields.'],
        ['title' => 'Reminders task list', 'text' => '“Vault name · reminders” contains the deadlines awaiting an answer within Monica’s reminder window. The due time is displayed in your device’s time zone. Answered or cancelled deadlines leave the list. These are tasks, not calendar events: your app must support CalDAV tasks (VTODO) to show them.'],
        ['title' => 'Make changes in Monica', 'text' => 'Both subscriptions are read-only. Change a recurring date on the contact in Monica. Answer or manage a reminder in Monica; you cannot complete, reschedule or delete it from the synced task list.'],
    ];

    /** @var list<array{title: string, text: string}> */
    public const array TROUBLESHOOTING = [
        ['title' => 'My password is rejected', 'text' => 'Use your Monica email address as the username and the complete API key as the password, with no extra spaces. Your account password will not work. If the key was revoked or you no longer have it, create a new one.'],
        ['title' => 'The app cannot find my account', 'text' => 'Copy the full server address from Connect your app, including /dav. Use manual setup or login with a URL when offered. Make sure your device can reach your Monica instance and trusts its HTTPS certificate. Opening the address in a browser is not a sync test.'],
        ['title' => 'A contact or field is missing', 'text' => 'Check that the vault’s address book is enabled in the app. Archived and names-only contacts are excluded. Check the supported fields above and whether the key allows sensitive fields. Information saved to another account on the device will not appear in Monica.'],
        ['title' => 'Dates appear, but reminders do not', 'text' => 'Enable the vault’s reminders list as well as its dates calendar. Your app must support CalDAV tasks (VTODO); support for calendar events alone is not enough.'],
        ['title' => 'A change is refused', 'text' => 'Calendars and reminder lists are read-only. A contact must have a name, and the contact representing you cannot be deleted or moved. For a version conflict, refresh the contact before editing again.'],
        ['title' => 'Sync is delayed or reports too many requests', 'text' => 'Refresh the app and check its sync schedule. Monica allows 60 requests per minute per user, shared with API requests. Separate device keys do not increase this limit. If the app hits it, let it retry after the limit resets.'],
    ];

    /** @var list<string> */
    public const array DISCONNECT = [
        'In Monica, open **Settings → Security and access → API keys** and revoke the key named after the device. It stops working on the next request. If you reused the key, every app using it loses access.',
        'Remove the Monica account from the device’s account or sync settings. Do not delete its contacts one by one while sync is still active.',
        'Check the device for downloaded or exported copies. Revoking a key stops future access; it does not remotely erase data already copied to the device.',
    ];

    public function instanceAddress(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public function serverAddress(): string
    {
        return $this->instanceAddress().'/dav';
    }

    public function lastUpdated(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-09-28');
    }

    public function editUrl(): string
    {
        return 'https://github.com/monicahq/monica/edit/main/app/ViewModels/Docs/CarddavDocsViewModel.php';
    }

    public function issueUrl(): string
    {
        return 'https://github.com/monicahq/monica/issues/new';
    }

    /** @return list<array{key: string, label: string, steps: list<string>, calendar: string, sources: array<string, string>}> */
    public function clients(): array
    {
        return [
            [
                'key' => 'ios', 'label' => 'iPhone / iPad',
                'steps' => [
                    'In **Settings**, open **Apps → Contacts → Contact Accounts → Add Account**. On older versions, Contacts is directly under Settings.',
                    'Choose **Other → Add CardDAV Account**. Enter the server address, your Monica email and your API key from the connection details above. Give the account a recognisable description, such as Monica.',
                    'Save the account, open **Contacts → Lists**, and select your vault’s address book.',
                ],
                'calendar' => 'In Settings, open **Apps → Calendar → Calendar Accounts → Add Account**, then **Other** (or **Add Other Account**) and **Add CalDAV Account**. Use the same connection details. Select the vault’s dates calendar in Calendar. Reminder tasks require an app that supports CalDAV task lists.',
                'sources' => ['Apple account setup' => 'https://support.apple.com/guide/iphone/ipha0d932e96/ios'],
            ],
            [
                'key' => 'mac', 'label' => 'Mac',
                'steps' => [
                    'In **Contacts → Settings → Accounts**, click **+** and choose **Other Contacts Account**.',
                    'Choose **Manual** if asked for an account type. Fill in the server address, your Monica email and your API key, then sign in.',
                    'Select the vault’s address book in Contacts. Check the account’s fetch settings to choose how often it updates.',
                ],
                'calendar' => 'In **Calendar → Add Account**, choose **Other CalDAV Account**, then **Manual**. Enter the same connection details and select the dates calendars you want. A separate app with CalDAV task support may be needed for reminder tasks.',
                'sources' => ['Apple Contacts setup' => 'https://support.apple.com/guide/contacts/adbk27096/mac', 'Apple Calendar setup' => 'https://support.apple.com/guide/calendar/icl4308d6701/mac'],
            ],
            [
                'key' => 'android', 'label' => 'Android / DAVx5',
                'steps' => [
                    'Open **DAVx5**, add an account, and choose login with a **URL and user name**.',
                    'Use the server address as the base URL, your Monica email as the username, and your API key as the password.',
                    'Finish creating the account, grant the permissions needed for contacts and calendar sync, then enable the vault address books you want in DAVx5. Start a sync and select those address books in your contacts app.',
                ],
                'calendar' => 'In the same DAVx5 account, enable the vault’s dates calendar and, if wanted, its reminders task list. DAVx5 syncs the data to your apps; showing reminder tasks also requires a compatible task app.',
                'sources' => ['DAVx5 setup and sync' => 'https://manual.davx5.com/introduction.html'],
            ],
            [
                'key' => 'thunderbird', 'label' => 'Thunderbird',
                'steps' => [
                    'Open **Address Book**. From the **New Address Book** menu, choose **Add CardDAV Address Book**.',
                    'Enter your Monica email as the username and the server address as the location. Continue and enter your API key when asked for a password.',
                    'Select the vault address books you want and finish setup.',
                ],
                'calendar' => 'Choose **New Calendar → On the Network** in Calendar. Enter your Monica email and the server address, use the API key when prompted, and select the discovered calendars. Look in **Tasks** for reminder lists that you enabled.',
                'sources' => ['Thunderbird CardDAV setup' => 'https://support.mozilla.org/en-US/questions/1392802', 'Thunderbird calendar setup' => 'https://support.mozilla.org/en-US/kb/creating-new-calendars'],
            ],
            [
                'key' => 'gnome', 'label' => 'Evolution / GNOME',
                'steps' => [
                    'In **Evolution**, choose **File → New → Address Book**, then select **CardDAV**.',
                    'Enter the server address as the URL and your Monica email as the user. Choose **Find Address Books** and enter your API key when prompted.',
                    'Choose a vault’s address book and save it. It is also available in GNOME Contacts. Repeat for another vault if needed.',
                ],
                'calendar' => 'In Evolution, choose **File → New → Calendar**, select **CalDAV**, and use the same URL and credentials with **Find Calendars**. For discovery of address books, calendars and task lists together, use **File → New → Collection account** and supply the server under its advanced options.',
                'sources' => ['Evolution contacts' => 'https://gnome.pages.gitlab.gnome.org/evolution/help/contacts-carddav.html', 'Evolution calendars' => 'https://gnome.pages.gitlab.gnome.org/evolution/help/calendar-caldav.html', 'Evolution collection accounts' => 'https://help.gnome.org/evolution/intro-account-types.html'],
            ],
            [
                'key' => 'kde', 'label' => 'KDE / Kontact',
                'steps' => [
                    'In Kontact’s **Contacts** view (KAddressBook), choose **File → New → Add Address Book** and select **DAV groupware resource**.',
                    'Enter your Monica email and API key. Use manual configuration for a server that is not in the provider list, choose CardDAV, and supply the server address.',
                    'Discover the available address books, select the vaults you want, and finish the resource setup.',
                ],
                'calendar' => 'Configure CalDAV in the DAV groupware resource using the same server address and credentials. Select the dates and reminders collections you want; calendars and to-dos are shown in Kontact’s calendar component.',
                'sources' => ['KAddressBook setup' => 'https://docs.kde.org/stable_kf6/en/kaddressbook/kaddressbook/getting-started.html'],
            ],
        ];
    }

    public function markdown(): string
    {
        $lines = ['# '.self::TITLE, '', self::LEAD, '', '## Overview', '', self::OVERVIEW, '', '**Deleting synced contacts.** '.self::DELETE_NOTICE, '', '## Before you connect', ''];
        $this->steps($lines, self::SETUP);
        $lines[] = '[Open Security settings]('.route('settings.security.index').'#api-keys)';
        $lines[] = '';
        $lines[] = '**Access to your vaults.** '.self::ACCESS_NOTICE;
        $lines[] = '';
        $lines[] = '## Connect your app';
        $lines[] = '';
        $lines[] = 'Menu names vary by app version. Contacts use CardDAV; calendars and reminder tasks use CalDAV.';
        $lines[] = '';
        $lines[] = "Server address: `{$this->serverAddress()}`";
        $lines[] = 'Username: your Monica email address';
        $lines[] = 'Password: your API key, not your account password';
        $lines[] = '';

        foreach ($this->clients() as $client) {
            $lines[] = '### '.$client['label'];
            $lines[] = '';
            $this->steps($lines, $client['steps']);
            $lines[] = '**Calendars and reminders.** '.$client['calendar'];
            $lines[] = '';

            foreach ($client['sources'] as $label => $url) {
                $lines[] = "[{$label}]({$url})";
            }

            $lines[] = '';
        }

        $lines[] = '## Check your connection';
        $lines[] = '';
        $this->steps($lines, self::CHECKS);
        $lines[] = self::TIMING;
        $lines[] = '';
        $lines[] = '## What syncs';
        $lines[] = '';
        $lines[] = self::CONTACTS;
        $lines[] = '';
        $lines[] = '| Information | What reaches your address book |';
        $lines[] = '| --- | --- |';

        foreach (self::FIELDS as $field) {
            $lines[] = "| {$field['label']} | {$field['text']} |";
        }

        $lines[] = '';
        $lines[] = self::SENSITIVE;
        $lines[] = '';

        foreach (['Editing and deleting' => self::CHANGES, 'Calendars and reminders' => self::CALENDARS, 'Troubleshooting' => self::TROUBLESHOOTING] as $title => $entries) {
            $lines[] = '## '.$title;
            $lines[] = '';

            foreach ($entries as $entry) {
                $lines[] = '**'.$entry['title'].'.** '.$entry['text'];
                $lines[] = '';
            }

            if ($title === 'Editing and deleting') {
                $lines[] = '**Moving between vaults.** '.self::MOVE_NOTICE;
                $lines[] = '';
            }
        }

        $lines[] = '## Disconnect a device';
        $lines[] = '';
        $this->steps($lines, self::DISCONNECT);

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $lines
     * @param  list<string>  $steps
     */
    private function steps(array &$lines, array $steps): void
    {
        foreach ($steps as $index => $step) {
            $lines[] = ($index + 1).'. '.$step;
        }

        $lines[] = '';
    }
}
