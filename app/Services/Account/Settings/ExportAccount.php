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
        $this->exportActivity($data);
        $this->exportGender($data);
        $this->exportContact($data);
        $this->exportActivityContact($data);
        $this->exportActivityStatistic($data);
        $this->exportActivityTypeCategory($data);
        $this->exportActivityType($data);
        $this->exportAddress($data);
        $this->exportCall($data);
        $this->exportCompany($data);
        $this->exportContactFieldType($data);
        $this->exportContactField($data);
        $this->exportContactTag($data);
        $this->exportContact($data);
        //$this->exportReminder($data);
        // $this->exportActivity($data);



        dd($this->sql);

        // a boucler sur les photos
        $this->exportContactPhoto($data);
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

        $listOfColumns = implode(",", $columns);

        foreach ($accountData as $singleSQLData) {
            $columnValues = [];

            $this->sql = $this->sql.'INSERT INTO '.$tableName.' ('.$listOfColumns.') values (';

            // build an array of values
            foreach ($columns as $key => $value) {
                $value = $singleSQLData->{$value};

                if (is_null($value)) {
                    $value = 'NULL';
                } elseif (!is_numeric($value)) {
                    $value = "'".addslashes($value)."'";
                }

                array_push($columnValues, $value);
            }

            $this->sql .= implode(',', $columnValues) . ');' . PHP_EOL;
        }
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

    /**
     * Export the Activity Contact table.
     *
     * @param array $data
     * @return string
     */
    private function exportActivityContact(array $data)
    {
        $columns = [
            'activity_id',
            'account_id',
            'contact_id',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('activity_contact', $foreignKey, $columns, $data);
    }

    /**
     * Export the Activity Statistic table.
     *
     * @param array $data
     * @return string
     */
    private function exportActivityStatistic(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'year',
            'count',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('activity_statistics', $foreignKey, $columns, $data);
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
     * Export the Address table.
     *
     * @param array $data
     * @return string
     */
    private function exportAddress(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'place_id',
            'contact_id',
            'name',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('addresses', $foreignKey, $columns, $data);
    }

    /**
     * Export the Call table.
     *
     * @param array $data
     * @return string
     */
    private function exportCall(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'called_at',
            'content',
            'contact_called',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('calls', $foreignKey, $columns, $data);
    }

    /**
     * Export the Company table.
     *
     * @param array $data
     * @return string
     */
    private function exportCompany(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'name',
            'website',
            'number_of_employees',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('companies', $foreignKey, $columns, $data);
    }

    /**
     * Export the Contact Field Type table.
     *
     * @param array $data
     * @return string
     */
    private function exportContactFieldType(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'name',
            'fontawesome_icon',
            'protocol',
            'delible',
            'type',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('contact_field_types', $foreignKey, $columns, $data);
    }

    /**
     * Export the Contact Field table.
     *
     * @param array $data
     * @return string
     */
    private function exportContactField(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'contact_field_type_id',
            'data',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('contact_fields', $foreignKey, $columns, $data);
    }

    /**
     * Export the Contact Tag table.
     *
     * @param array $data
     * @return string
     */
    private function exportContactTag(array $data)
    {
        $columns = [
            'contact_id',
            'tag_id',
            'account_id',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('contact_tag', $foreignKey, $columns, $data);
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
            'uuid',
            'is_starred',
            'is_partial',
            'is_active',
            'is_dead',
            'deceased_special_date_id',
            'deceased_reminder_id',
            'last_talked_to',
            'stay_in_touch_frequency',
            'stay_in_touch_trigger_date',
            'birthday_special_date_id',
            'birthday_reminder_id',
            'first_met_through_contact_id',
            'first_met_special_date_id',
            'first_met_reminder_id',
            'first_met_where',
            'first_met_additional_info',
            'job',
            'company',
            'food_preferences',
            'avatar_source',
            'avatar_gravatar_url',
            'avatar_adorable_uuid',
            'avatar_adorable_url',
            'avatar_default_url',
            'avatar_photo_id',
            'has_avatar',
            'avatar_external_url',
            'avatar_file_name',
            'avatar_location',
            'gravatar_url',
            'last_consulted_at',
            'number_of_views',
            'created_at',
            'updated_at',
            'default_avatar_color',
            'has_avatar_bool'
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('contacts', $foreignKey, $columns, $data);
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


    // a part en bouclant sur les contacts
    /**
     * Export the Contact Photo table.
     *
     * @param array $data
     * @return string
     */
    private function exportContactPhoto(array $data)
    {
        $columns = [
            'contact_id',
            'account_id',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('contact_photo', $foreignKey, $columns, $data);
    }
}
