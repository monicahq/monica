<?php

namespace App\Jobs;

use App\Helpers\DBHelper;
use App\Services\Account\Settings\ExportAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Auth;

class ExportAccountAsSQL
{
    use Dispatchable, SerializesModels;

    protected $ignoredTables = [
        'accounts',
        'activity_type_activities',
        'activity_types',
        'api_usage',
        'cache',
        'countries',
        'crons',
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
        'terms',
        'u2f_key',
        'users',
        'webauthn_keys',
    ];

    protected $ignoredColumns = [
        'stripe_id',
        'card_brand',
        'card_last_four',
        'trial_ends_at',
    ];

    protected $file = '';
    protected $path = '';

    /**
     * Storage disk used to store the exported file.
     * @var string
     */
    public const STORAGE = 'public';

    /**
     * Create a new job instance.
     *
     * @param string|null $file
     * @param string|null $path
     */
    public function __construct($file = null, $path = null)
    {
        $this->path = $path ?? 'exports/';
        $this->file = $file ?? rand().'.sql';
    }

    /**
     * Execute the job.
     *
     * @return string
     */
    public function handle()
    {
        $downloadPath = $this->path.$this->file;

        $sql = app(ExportAccount::class)
                ->execute([
                    'account_id' => Auth::user()->account_id,
                    'user_id' => Auth::user()->id,
                ]);

        Storage::disk(self::STORAGE)->put($downloadPath, $sql);

        return $downloadPath;
    }
}
