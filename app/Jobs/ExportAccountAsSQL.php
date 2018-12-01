<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

namespace App\Jobs;

use App\Helpers\DBHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;

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
        'currencies',
        'default_activity_types',
        'default_activity_type_categories',
        'default_contact_field_types',
        'default_contact_modules',
        'default_life_event_categories',
        'default_life_event_types',
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

        $tables = DBHelper::getTables();

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
        $tableName = 'accounts';
        $tableData = DB::table($tableName)
            ->where('id', '=', $account->id)
            ->get()
            ->toArray();
        foreach ($tableData as $data) {
            $data = (array) $data;
            $values = [
                $data['id'],
                "'".addslashes($data['api_key'])."'",
                $data['number_of_invitations_sent'] ?? 'NULL',
            ];
            $newSQLLine = 'INSERT INTO '.$tableName.' (id, api_key, number_of_invitations_sent) VALUES (';
            $newSQLLine .= implode(',', $values).');'.PHP_EOL;
            $sql .= $newSQLLine;
        }

        Storage::disk(self::STORAGE)->put($downloadPath, $sql);

        return $downloadPath;
    }
}
