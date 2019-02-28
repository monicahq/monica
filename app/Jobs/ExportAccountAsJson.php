<?php

namespace App\Jobs;

use App\Helpers\DBHelper;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;

class ExportAccountAsJson
{
    use Dispatchable, SerializesModels;

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
        'terms',
        'u2f_key',
        'users',
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
        $this->file = $file ?? rand().'.json';
    }

    /**
     * Execute the job.
     *
     * @return string
     */
    public function handle()
    {
        $downloadPath = $this->path.$this->file;

        /** @var User $user */
        $user = auth()->user();
        $account = $user->account;

        $exported_at = now();
        $json_output = <<< END_HEAD
{
  "export_meta": {
    "format": 1,
    "username": "{$user->first_name} {$user->last_name}",
    "filename": "{$this->file}",
    "exported": "{$exported_at}"
  },
END_HEAD;


        $tables = DBHelper::getTables();

        // Looping over the tables
        foreach ($tables as $table) {
            $tableName = $table->table_name;

            if (in_array($tableName, $this->ignoredTables)) {
                continue;
            }

            $json_output .= "\n  \"$tableName\": [";

            $tableJsonRows = [];
            $tableData = DB::table($tableName)->get();

            // Looping over the rows
            foreach ($tableData as $data) {
                $tableValues = [];
                $skipLine = false;

                // Looping over the values
                foreach ($data as $columnName => $value) {
                    if ($columnName == 'account_id' && $value !== $account->id) {
                        $skipLine = true;
                        break;
                    }

                    $value = '"'.$columnName.'": '.json_encode($value);
                    array_push($tableValues, $value);
                }

                if (! $skipLine) {
                    $newSQLLine = implode(",\n      ", $tableValues);
                    array_push($tableJsonRows, "{\n      ".$newSQLLine."\n    }");
                }
            }

            if(count($tableJsonRows) > 0) {
                $json_output .= "\n    ".implode(",\n    ", $tableJsonRows)."\n  ],";
            } else {
                $json_output .= "],";
            }
        }

        // Specific to `accounts` table
        $tableName = 'accounts';
        $tableData = DB::table($tableName)
            ->where('id', '=', $account->id)
            ->get()
            ->toArray();

        $firstElement = true;
        $json_output .= "\n  \"$tableName\": [";
        foreach ($tableData as $data) {
            $data = (array) $data;
            $values = [
                $data['id'],
                json_encode($data['api_key']),
                json_encode($data['number_of_invitations_sent']),
            ];

            if($firstElement)
                $firstElement = false;
            else
                $json_output .= ",";

            $json_output .= <<< END

    {
      "id": ${values[0]},
      "api_key": ${values[1]},
      "number_of_invitations_sent": ${values[2]}
    }
END;
        }

        if(!$firstElement)
            $json_output .= "\n  ";

        $json_output .= "]\n}";

        Storage::disk(self::STORAGE)->put($downloadPath, $json_output);

        return $downloadPath;
    }
}
