<?php

declare(strict_types=1);
use App\Enums\SsoProviderEnum;
use Illuminate\Support\Str;
use Laravel\Socialite\SocialiteServiceProvider;
use SocialiteProviders\Apple\Provider as AppleProvider;

if (! function_exists('readVersion')) {
    function readVersion(string $file, string $gitCommand, ?string $default = null): ?string
    {
        $content = null;
        if (is_file($file)) {
            $content = file_get_contents($file);
        } elseif (is_dir(base_path('.git'))) {
            $command = Str::of($gitCommand)
                ->start('git ')
                ->replaceStart('git', 'git --git-dir "'.base_path('.git').'"');
            $content = trim(exec("$command 2>".(substr(php_uname(), 0, 7) === 'Windows' ? 'NUL' : '/dev/null')));
        }

        return trim($content ?? $default);
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | Remember me
    |--------------------------------------------------------------------------
    |
    | How long a session survives when somebody ticks "remember me" on the sign
    | in screen. The screen names this number, so changing it here changes what
    | the visitor is promised.
    |
    */

    'remember_duration_days' => env('MONICA_REMEMBER_DURATION_DAYS', 400),

    /*
    |--------------------------------------------------------------------------
    | Login using a link
    |--------------------------------------------------------------------------
    |
    | Whether somebody may sign in with a link emailed to them rather than with a password.
    | Switching it off takes the request screen, the submission and the link itself out of
    | the instance rather than merely hiding the offer.
    |
    */
    'magic_link_enabled' => (bool) env('MONICA_MAGIC_LINK_ENABLED', true),

    'magic_link_duration_minutes' => env('MONICA_MAGIC_LINK_DURATION_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Registration
    |--------------------------------------------------------------------------
    |
    | Whether somebody may create an account on this instance. Switching it off takes the sign up
    | screen, both of its submissions and the API endpoint out of the instance, and refuses a
    | first sign in through an outside provider. Whoever already has an account is not affected.
    |
    */
    'registration_enabled' => (bool) env('MONICA_REGISTRATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Preview list
    |--------------------------------------------------------------------------
    |
    | Whether a visitor may leave an email address to wait for an account while registration is
    | closed. The root address then shows a card taking that address, and an administrator admits
    | people from the admin space. It does nothing while registration is open.
    |
    */
    'preview_list_enabled' => (bool) env('MONICA_PREVIEW_LIST_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Support
    |--------------------------------------------------------------------------
    |
    | Whether people may ask the administrators of this instance for help from inside the
    | application. Off by default. Switching it off takes every support screen, the queue of the
    | administrators, the API endpoints, the MCP tools and their documentation out of the
    | instance rather than merely hiding the offer. Requests already written are kept.
    |
    */
    'support_enabled' => (bool) env('MONICA_SUPPORT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | What's new
    |--------------------------------------------------------------------------
    |
    | Whether the instance shows What's new, the changelog of Monica, with its votes and comments.
    | Off by default. Switching it off takes the screen, the link in the top bar, the comments of
    | the administrators, the API endpoints and their documentation out of the instance. Comments
    | and votes already written are kept.
    |
    */
    'changelog_enabled' => (bool) env('MONICA_CHANGELOG_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Search engine indexing
    |--------------------------------------------------------------------------
    |
    | Whether search engines may list the pages anybody can read: the documentation, the guide and
    | the marketplace. Switching it off asks them to leave out every page of the instance. Every
    | other page, a private link included, is left out whatever this says.
    |
    */
    'search_engine_indexing' => (bool) env('MONICA_SEARCH_ENGINE_INDEXING', true),

    /*
    |--------------------------------------------------------------------------
    | Signing in through an outside provider
    |--------------------------------------------------------------------------
    |
    | Which providers this instance could offer, meaning the ones whose driver is
    | installed: every one the product knows, once Socialite is there, except
    | Apple, whose package is suggested rather than required. A provider
    | is only offered once its settings are supplied as well, which
    | SsoProviderAvailability decides. The presence of a package is read here
    | rather than where it is used, so a test can reach either state by
    | overriding this key.
    |
    */
    'sso_installed' => class_exists(SocialiteServiceProvider::class)
        ? array_values(array_diff(SsoProviderEnum::values(), class_exists(AppleProvider::class) ? [] : ['apple']))
        : [],

    /*
    |--------------------------------------------------------------------------
    | Legal pages
    |--------------------------------------------------------------------------
    |
    | Where the terms of use and the privacy policy live. They sit on the public
    | site rather than in the application, so an instance that runs its own copy
    | of those documents can point at them here.
    |
    */

    'terms_url' => env('MONICA_TERMS_URL', 'https://monicahq.com/terms'),

    'privacy_url' => env('MONICA_PRIVACY_URL', 'https://monicahq.com/privacy'),

    /*
    |--------------------------------------------------------------------------
    | Self hosted
    |--------------------------------------------------------------------------
    |
    | Whether this instance runs outside of the cloud one. An account created on
    | a self hosted instance is never metered: it has no storage ceiling and no
    | rate limit applies to it.
    |
    */

    'self_hosted' => env('MONICA_SELF_HOSTED', false),

    /*
    |--------------------------------------------------------------------------
    | Free storage
    |--------------------------------------------------------------------------
    |
    | How many bytes of files an account gets on the free offer of the cloud
    | instance, before the one time purchase. The ceiling is stored on the
    | account when it is created, so raising this number here does not raise it
    | for the accounts that already exist.
    |
    */

    'free_storage_limit_in_bytes' => env('MONICA_FREE_STORAGE_LIMIT_IN_BYTES', 104857600),

    'free_vault_limit' => env('MONICA_FREE_VAULT_LIMIT', 1),

    'free_contact_limit' => env('MONICA_FREE_CONTACT_LIMIT', 30),

    'paid_storage_limit_in_bytes' => env('MONICA_PAID_STORAGE_LIMIT_IN_BYTES', 10737418240),

    'offer_tag' => env('MONICA_OFFER_TAG', 'offer'),

    'product_tag' => [
        'key' => env('MONICA_PRODUCT_TAG_KEY', 'version'),
        'value' => env('MONICA_PRODUCT_TAG_VALUE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Orphaned files
    |--------------------------------------------------------------------------
    |
    | How many days a file nothing points at is kept before the sweep takes it.
    | It has to be longer than the window in which a record can be restored to
    | an earlier version, or a restore recovers a pointer to nothing.
    |
    */

    'orphaned_file_grace_period_in_days' => env('MONICA_ORPHANED_FILE_GRACE_PERIOD_IN_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | How far ahead reminder deadlines are generated
    |--------------------------------------------------------------------------
    |
    | A rule is expanded into concrete deadlines over a rolling window rather
    | than for ever, because a yearly rule multiplied across hundreds of records
    | would otherwise fill a table nobody reads. Ninety days is long enough that
    | no realistic outage of the generator loses a deadline, since the sweep runs
    | daily and only has to have run once inside the window.
    |
    | Shortening it below the interval between two runs of the generator loses
    | deadlines outright.
    |
    */

    'reminder_generation_window_in_days' => env('MONICA_REMINDER_GENERATION_WINDOW_IN_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | How many times a notification is attempted
    |--------------------------------------------------------------------------
    |
    | A channel that refuses a notification is tried again, and abandoned after
    | this many attempts with the reason written on the row. Nothing is deleted
    | on failure, so a notification nobody received is still visible as one.
    |
    */

    'notification_maximum_attempts' => env('MONICA_NOTIFICATION_MAXIMUM_ATTEMPTS', 3),

    'notification_retention_months' => env('MONICA_NOTIFICATION_RETENTION_MONTHS', 12),

    /*
    |--------------------------------------------------------------------------
    | MCP history
    |--------------------------------------------------------------------------
    |
    | Every call an assistant makes to a tool is kept this many days, then
    | removed by the daily prune. The history screen states the figure.
    |
    */

    'mcp_call_retention_in_days' => env('MONICA_MCP_CALL_RETENTION_IN_DAYS', 90),

    'notification_channel_failure_threshold' => env('MONICA_NOTIFICATION_CHANNEL_FAILURE_THRESHOLD', 5),

    'notification_proof_code_minutes' => env('MONICA_NOTIFICATION_PROOF_CODE_MINUTES', 10),

    'notification_proof_maximum_attempts' => env('MONICA_NOTIFICATION_PROOF_MAXIMUM_ATTEMPTS', 5),

    'notification_proof_maximum_sends' => env('MONICA_NOTIFICATION_PROOF_MAXIMUM_SENDS', 3),

    'notification_proof_resend_seconds' => env('MONICA_NOTIFICATION_PROOF_RESEND_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | The largest single file
    |--------------------------------------------------------------------------
    |
    | How many kilobytes one upload may weigh. The account's own storage ceiling
    | is the real limit and is enforced where the file is written; this is here
    | so a form can refuse an enormous file with a sentence under the control,
    | rather than letting PHP truncate it and reporting nothing at all.
    |
    | It has to stay under `upload_max_filesize` and `post_max_size` in the php
    | configuration, since neither of those can be argued with from here.
    |
    */

    'max_file_size_in_kilobytes' => env('MONICA_MAX_FILE_SIZE_IN_KILOBYTES', 10240),

    /*
    |--------------------------------------------------------------------------
    | What this instance is, and which release it runs
    |--------------------------------------------------------------------------
    |
    | The status endpoint of the API reports both, so that somebody pointing a
    | monitor at an instance can tell which one answered and what it was running
    | when it did. The version is the release rather than a commit: a self hosted
    | instance is a copy somebody chose to install, and the number they read here
    | is the number they compare against the changelog.
    |
    | The name is the host, and it defaults to the application URL rather than to
    | a fixed string, so an instance nobody configured still says something true
    | about itself.
    |
    */

    'version' => env('MONICA_VERSION', readVersion(__DIR__.'/.version', 'git describe --abbrev=0 --tags', '0.0.0')),

    'instance' => env('MONICA_INSTANCE', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),

    /*
    |--------------------------------------------------------------------------
    | How many things one page of an API list holds
    |--------------------------------------------------------------------------
    |
    | Every list the API answers is paginated the same way, which the reference
    | promises under Getting started: `page` moves through the results and
    | `per_page` sizes them. The first value here is what a caller gets when it
    | asks for nothing, and the second is the ceiling: a request for more is
    | brought back down to it rather than refused, because a page size somebody
    | typed wrongly is not a reason to fail their call.
    |
    | It sits here rather than in the framework's own configuration because the
    | ceiling is an operator's decision. A hosted instance and a self hosted one
    | run the same product and differ in quotas, and this is one of them.
    |
    */

    'api_default_items_per_page' => env('MONICA_API_DEFAULT_ITEMS_PER_PAGE', 25),

    'api_maximum_items_per_page' => env('MONICA_API_MAXIMUM_ITEMS_PER_PAGE', 100),

    /*
    |--------------------------------------------------------------------------
    | The queue behind each priority
    |--------------------------------------------------------------------------
    |
    | The application sends its background work at one of three priorities,
    | high, default and low, and these name the queue each one lands on. Work
    | that names no queue goes to the default one. A value left blank keeps the
    | shipped name. The worker command keeps listing the priorities,
    | `high,default,low`, and reaches the queues named here. A priority is sent
    | to the queue named here and no further, so to run everything on one queue,
    | give all three the same name.
    |
    | A worker reads these once, when it starts, so a change needs the workers
    | restarted. Change a name while its queue is empty: work already waiting
    | on the old name is not moved.
    |
    */

    'queues' => [
        'high' => env('MONICA_QUEUE_HIGH', 'high'),
        'default' => env('MONICA_QUEUE_DEFAULT', 'default'),
        'low' => env('MONICA_QUEUE_LOW', 'low'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Screenshot Link
    |--------------------------------------------------------------------------
    |
    | A signed link that signs somebody in, so a screenshot tool that can only open a URL can reach
    | a screen behind the sign in.
    |
    | Three things have to be true at once for it to work: the environment is a development one,
    | this setting has been switched on by hand, and the instance answers on a development host.
    | The host is asked for because `.env.example` ships `APP_ENV=local`, so an instance deployed
    | from it would otherwise be one forgotten setting away from a way in. Above all that the link
    | is signed with the application key and expires, so minting one already takes everything an
    | attacker would need anyway.
    |
    */
    'screenshot_link' => [
        'enabled' => (bool) env('MONICA_SCREENSHOT_LINK_ENABLED', false),
        'lifetime_in_minutes' => (int) env('MONICA_SCREENSHOT_LINK_LIFETIME_IN_MINUTES', 5),
        'allowed_hosts' => [
            'localhost',
            '127.0.0.1',
            '[::1]',
            '*.test',
        ],
    ],

];
