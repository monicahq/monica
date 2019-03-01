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

        $json_output = '';
        $this->generate_json_header($json_output, $user);

        // Looping over the tables
        $tables = DBHelper::getTables();
        foreach ($tables as $table) {
            $this->process_table($json_output, $account, $table->table_name);
        }

        // Accounts table has to be handled differently
        $this->process_accounts_table($json_output, $account);

        // Close the JSON object and write it to disk
        $json_output .= "\n}";

        Storage::disk(self::STORAGE)->put($downloadPath, $json_output);

        return $downloadPath;
    }

    private function generate_json_header(string &$json_output, User $user)
    {
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
    }

    private function process_table(string &$json_output, $account, string $tableName)
    {
        if (in_array($tableName, ExportAccountAsSQL::ignoredTables)) {
            // Skip blacklisted tables. The blacklist is shared with the ExportAccountAsSQL job.
            return;
        }

        $json_output .= "\n  \"$tableName\": [";
        $tableJsonRows = [];

        // Looping over the rows
        $tableData = DB::table($tableName)->get();
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

        if (count($tableJsonRows) > 0) {
            $json_output .= "\n    ".implode(",\n    ", $tableJsonRows)."\n  ";
        }

        $json_output .= '],';
    }

    private function process_accounts_table(string &$json_output, $account)
    {
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

            if ($firstElement) {
                $firstElement = false;
            } else {
                $json_output .= ',';
            }

            $json_output .= <<< END

    {
      "id": ${values[0]},
      "api_key": ${values[1]},
      "number_of_invitations_sent": ${values[2]}
    }
END;
        }

        if (! $firstElement) {
            $json_output .= "\n  ";
        }

        $json_output .= ']';
    }
}
