<?php

namespace App\Services\Account\Settings;

use App\Exceptions\NoAccountException;
use App\Models\Account\Account;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use App\Models\Contact\Document;
use App\Models\User\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ExportAccount extends BaseService
{
    protected $sql;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'user_id' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Export account as SQL.
     *
     * @param array $data
     * @return string
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $user = User::findOrFail($data['user_id']);

        $this->sql = '# ************************************************************
# '.$user->first_name.' '.$user->last_name." dump of data
# Export date: ".now().'
# ************************************************************

'.PHP_EOL;

        $this->exportAccount($data);
        $this->exportGender($data);
        //$this->exportContact($data);
        //$this->exportReminder($data);
        // $this->exportActivityTypeCategory($data);
        // $this->exportActivityType($data);
        // $this->exportActivity($data);
        // $this->exportActivity($data);

        dd($this->sql);
    }

    private function buildInsertSQLQuery(string $tableName, string $foreignKey, array $columns, array $data)
    {
        $accountData = DB::table($tableName)
            ->select($columns)
            ->where($foreignKey, $data['account_id'])
            ->get();

        if (!$accountData) {
            throw new NoAccountException();
        }

        if ($accountData->count() == 0) {
            return;
        }

        foreach ($accountData as $singleSQLData) {
            $columnValues = [];

            $this->sql = $this->sql.'INSERT INTO '.$tableName.' ('.implode(",", $columns).') values (';

            // build an array of values
            foreach ($columns as $columns => $value) {

                $value = $singleSQLData->{$value};

                if (is_null($value)) {
                    $value = 'NULL';
                } elseif (!is_numeric($value)) {
                    $value = "'".addslashes($value)."'";
                }

                array_push($columnValues, $value);
            }
        }

        $this->sql .= implode(',', $columnValues).');'.PHP_EOL;
    }

    /**
     * Export the Account table.
     *
     * @param array $data
     * @return string
     */
    private function exportAccount(array $data)
    {
        $columns = [
            'id',
            'api_key',
            'number_of_invitations_sent',
        ];

        $foreignKey = 'id';

        $this->buildInsertSQLQuery('accounts', $foreignKey, $columns, $data);
    }

    /**
     * Export the Gender table.
     *
     * @param array $data
     * @return string
     */
    private function exportGender(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'name',
            'type',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('genders', $foreignKey, $columns, $data);
    }

    /**
     * Export the Contact table.
     *
     * @param array $data
     * @return string
     */
    private function exportContact(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'first_name',
            'middle_name',
            'last_name',
            'nickname',
            'gender_id',
            'description',
            'deceased_special_date_id',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('contacts', $foreignKey, $columns, $data);
    }

    /**
     * Export the Activity Type Category table.
     *
     * @param array $data
     * @return string
     */
    private function exportActivityTypeCategory(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'name',
            'translation_key',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('activity_type_categories', $foreignKey, $columns, $data);
    }

    /**
     * Export the Activity Type table.
     *
     * @param array $data
     * @return string
     */
    private function exportActivityType(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'activity_type_category_id',
            'name',
            'translation_key',
            'location_type',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('activity_types', $foreignKey, $columns, $data);
    }

    /**
     * Export the Activity table.
     *
     * @param array $data
     * @return string
     */
    private function exportActivity(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'activity_type_id',
            'summary',
            'description',
            'date_it_happened',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('activities', $foreignKey, $columns, $data);
    }
}
