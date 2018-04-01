<?php

namespace App\Jobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;

class ExportAccountAsSQL
{
    use Dispatchable, SerializesModels;

    protected $ignoredTables = [
        'accounts',
        'activity_type_groups',
        'activity_types',
        'api_usage',
        'cache',
        'countries',
        'currencies',
        'default_contact_field_types',
        'failed_jobs',
        'import_jobs',
        'import_job_reports',
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

        $user = auth()->user();
        $account = $user->account;

        $sql = '# ************************************************************
# '.$user->first_name.' '.$user->last_name." dump of data
# {$this->file}
# Export date: ".now().'
# ************************************************************

'.PHP_EOL;

        $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema="monica"');

        // Looping over the tables
        foreach ($tables as $table) {
            $tableName = $table->table_name;

            if (in_array($tableName, $this->ignoredTables)) {
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
                    if ($columnName == 'account_id' && $value !== $account->id) {
                        $skipLine = true;
                        break;
                    }

                    if (is_null($value)) {
                        $value = 'NULL';
                    } elseif (! is_numeric($value)) {
                        $value = "'".addslashes($value)."'";
                    }

                    array_push($tableValues, $value);
                }

                if (! $skipLine) {
                    $newSQLLine .= implode(',', $tableValues).');'.PHP_EOL;
                    $sql .= $newSQLLine;
                }
            }
        }

        // Specific to `accounts` table
        $accounts = array_filter($tables, function ($e) {
            return $e->table_name == 'accounts';
        }
        )[0];
        $tableName = $accounts->table_name;
        $tableData = DB::table($tableName)->get()->toArray();
        foreach ($tableData as $data) {
            $newSQLLine = 'INSERT INTO '.$tableName.' VALUES (';
            $data = (array) $data;
            if ($data['id'] === $account->id):
                $values = [
                    $data['id'],
                    "'".addslashes($data['api_key'])."'",
                    $data['number_of_invitations_sent'] !== null
                        ? $data['number_of_invitations_sent']
                        : 'NULL',
                ];
            $newSQLLine .= implode(',', $values).');'.PHP_EOL;
            $sql .= $newSQLLine;
            endif;
        }

        Storage::disk(config('filesystems.default'))->put($downloadPath, $sql);

        return $downloadPath;
    }
}
