<?php

namespace App\Services\Account\Settings;

use App\Helpers\DBHelper;
use App\Models\User\User;
use Illuminate\Support\Str;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SqlExportAccount extends BaseService
{
    /** @var string */
    protected $tempFileName;

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
     * @param  array  $data
     * @return string
     */
    public function execute(array $data): string
    {
        $this->validate($data);

        $user = User::findOrFail($data['user_id']);

        $this->tempFileName = 'temp/'.Str::random(40).'.sql';

        $this->writeExport($data, $user);

        return $this->tempFileName;
    }

    /**
     * Export data in temp file.
     *
     * @param  array  $data
     * @param  User  $user
     */
    private function writeExport(array $data, User $user)
    {
        $sql = '# ************************************************************
# '.$user->first_name.' '.$user->last_name.' dump of data
# Export date: '.now().'
# How to use:
# * create a fresh database
# * run migrations (`php artisan migrate`)
# * import this sql file
# ************************************************************

SET FOREIGN_KEY_CHECKS=0;
'.PHP_EOL;

        $this->writeToTempFile($sql);

        $this->exportAccount($data);
        $this->exportActivity($data);
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
        $this->exportConversation($data);
        $this->exportDays($data);
        $this->exportDebt($data);
        $this->exportDocument($data);
        $this->exportEmotionCall($data);
        $this->exportEntries($data);
        $this->exportGender($data);
        $this->exportGift($data);
        $this->exportInvitation($data);
        $this->exportJournalEntry($data);
        $this->exportLifeEventCategory($data);
        $this->exportLifeEventType($data);
        $this->exportLifeEvent($data);
        $this->exportMessage($data);
        $this->exportMetaDataLoveRelationship($data);
        $this->exportModule($data);
        $this->exportNote($data);
        $this->exportOccupation($data);
        $this->exportPet($data);
        $this->exportPhoto($data);
        $this->exportPlace($data);
        $this->exportRecoveryCode($data);
        $this->exportRelationTypeGroup($data);
        $this->exportRelationType($data);
        $this->exportRelationship($data);
        $this->exportReminderOutbox($data);
        $this->exportReminderRule($data);
        $this->exportReminder($data);
        $this->exportSpecialDate($data);
        $this->exportTag($data);
        $this->exportTask($data);
        $this->exportTermUser($data);
        $this->exportUser($data);
        $this->exportWeather($data);
        $this->exportContactPhoto($data);
        $this->exportAuditLogs($data);

        $sql = 'SET FOREIGN_KEY_CHECKS=1;';
        $this->writeToTempFile($sql);
    }

    /**
     * Create the Insert query for the given table.
     *
     * @param  string  $tableName
     * @param  string  $foreignKey
     * @param  array  $columns
     * @param  array  $data
     * @return void
     */
    private function buildInsertSQLQuery(string $tableName, string $foreignKey, array $columns, array $data)
    {
        $accountData = DB::table($tableName)
            ->select($columns)
            ->where($foreignKey, $data['account_id'])
            ->get();

        if ($accountData->count() == 0) {
            return;
        }

        // adding a ` for each column
        $listOfColumns = array_map(function ($column) {
            return '`'.$column.'`';
        }, $columns);
        $listOfColumns = implode(',', $listOfColumns);

        $sql = 'INSERT IGNORE INTO '.DBHelper::getTable($tableName).' ('.$listOfColumns.') VALUES'.PHP_EOL;

        $insertValues = [];
        foreach ($accountData as $singleSQLData) {
            $columnValues = [];

            // build an array of values
            foreach ($columns as $value) {
                $value = $singleSQLData->{$value};

                if (is_null($value)) {
                    $value = 'NULL';
                } elseif (! is_numeric($value)) {
                    $value = "'".addslashes($value)."'";
                }

                array_push($columnValues, $value);
            }

            array_push($insertValues, ' ('.implode(',', $columnValues).')');
        }
        $sql .= implode(','.PHP_EOL, $insertValues);
        $this->writeToTempFile($sql.';'.PHP_EOL);
    }

    /**
     * Write to a temp file.
     *
     * @return void
     */
    private function writeToTempFile(string $sql)
    {
        Storage::disk('local')
            ->append($this->tempFileName, $sql);
    }

    /**
     * Export the Account table.
     *
     * @param  array  $data
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
     * @param  array  $data
     */
    private function exportActivity(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'activity_type_id',
            'summary',
            'description',
            'happened_at',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('activities', $foreignKey, $columns, $data);
    }

    /**
     * Export the Activity Contact table.
     *
     * @param  array  $data
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
     * @param  array  $data
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
     * @param  array  $data
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
     * @param  array  $data
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
     * @param  array  $data
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
     * @param  array  $data
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
     * @param  array  $data
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
     * @param  array  $data
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
     * @param  array  $data
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
     * @param  array  $data
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
     * @param  array  $data
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
            'has_avatar_bool',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('contacts', $foreignKey, $columns, $data);
    }

    /**
     * Export the Conversation table.
     *
     * @param  array  $data
     */
    private function exportConversation(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'contact_field_type_id',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('conversations', $foreignKey, $columns, $data);
    }

    /**
     * Export the Day table.
     *
     * @param  array  $data
     */
    private function exportDays(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'date',
            'rate',
            'comment',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('days', $foreignKey, $columns, $data);
    }

    /**
     * Export the Debt table.
     *
     * @param  array  $data
     */
    private function exportDebt(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'in_debt',
            'status',
            'amount',
            'currency_id',
            'reason',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('debts', $foreignKey, $columns, $data);
    }

    /**
     * Export the Document table.
     *
     * @param  array  $data
     */
    private function exportDocument(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'original_filename',
            'new_filename',
            'filesize',
            'type',
            'mime_type',
            'number_of_downloads',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('documents', $foreignKey, $columns, $data);
    }

    /**
     * Export the Emotion Call table.
     *
     * @param  array  $data
     */
    private function exportEmotionCall(array $data)
    {
        $columns = [
            'account_id',
            'call_id',
            'emotion_id',
            'contact_id',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('emotion_call', $foreignKey, $columns, $data);
    }

    /**
     * Export the Entries table.
     *
     * @param  array  $data
     */
    private function exportEntries(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'title',
            'post',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('entries', $foreignKey, $columns, $data);
    }

    /**
     * Export the Gender table.
     *
     * @param  array  $data
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
     * Export the Gift table.
     *
     * @param  array  $data
     */
    private function exportGift(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'name',
            'comment',
            'url',
            'amount',
            'currency_id',
            'status',
            'date',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('gifts', $foreignKey, $columns, $data);
    }

    /**
     * Export the Invitation table.
     *
     * @param  array  $data
     */
    private function exportInvitation(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'invited_by_user_id',
            'email',
            'invitation_key',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('invitations', $foreignKey, $columns, $data);
    }

    /**
     * Export the Journal Entry table.
     *
     * @param  array  $data
     */
    private function exportJournalEntry(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'date',
            'journalable_id',
            'journalable_type',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('journal_entries', $foreignKey, $columns, $data);
    }

    /**
     * Export the Life Event Category table.
     *
     * @param  array  $data
     */
    private function exportLifeEventCategory(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'name',
            'default_life_event_category_key',
            'core_monica_data',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('life_event_categories', $foreignKey, $columns, $data);
    }

    /**
     * Export the Life Event Type table.
     *
     * @param  array  $data
     */
    private function exportLifeEventType(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'life_event_category_id',
            'name',
            'default_life_event_type_key',
            'core_monica_data',
            'specific_information_structure',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('life_event_types', $foreignKey, $columns, $data);
    }

    /**
     * Export the Life Event table.
     *
     * @param  array  $data
     */
    private function exportLifeEvent(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'life_event_type_id',
            'reminder_id',
            'name',
            'note',
            'happened_at',
            'happened_at_month_unknown',
            'happened_at_day_unknown',
            'specific_information',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('life_events', $foreignKey, $columns, $data);
    }

    /**
     * Export the Message table.
     *
     * @param  array  $data
     */
    private function exportMessage(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'conversation_id',
            'content',
            'written_at',
            'written_by_me',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('messages', $foreignKey, $columns, $data);
    }

    /**
     * Export the Metadata love relationship table.
     *
     * @param  array  $data
     */
    private function exportMetaDataLoveRelationship(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'relationship_id',
            'is_active',
            'notes',
            'meet_date',
            'official_date',
            'breakup_date',
            'breakup_reason',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('metadata_love_relationships', $foreignKey, $columns, $data);
    }

    /**
     * Export the Module table.
     *
     * @param  array  $data
     */
    private function exportModule(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'key',
            'translation_key',
            'active',
            'delible',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('modules', $foreignKey, $columns, $data);
    }

    /**
     * Export the Note table.
     *
     * @param  array  $data
     */
    private function exportNote(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'body',
            'is_favorited',
            'favorited_at',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('notes', $foreignKey, $columns, $data);
    }

    /**
     * Export the Occupation table.
     *
     * @param  array  $data
     */
    private function exportOccupation(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'company_id',
            'title',
            'description',
            'salary',
            'salary_unit',
            'currently_works_here',
            'start_date',
            'end_date',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('occupations', $foreignKey, $columns, $data);
    }

    /**
     * Export the Pet table.
     *
     * @param  array  $data
     */
    private function exportPet(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'pet_category_id',
            'name',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('pets', $foreignKey, $columns, $data);
    }

    /**
     * Export the Photo table.
     *
     * @param  array  $data
     */
    private function exportPhoto(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'original_filename',
            'new_filename',
            'filesize',
            'mime_type',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('photos', $foreignKey, $columns, $data);
    }

    /**
     * Export the Place table.
     *
     * @param  array  $data
     */
    private function exportPlace(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'street',
            'city',
            'province',
            'postal_code',
            'country',
            'latitude',
            'longitude',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('places', $foreignKey, $columns, $data);
    }

    /**
     * Export the Recovery Code table.
     *
     * @param  array  $data
     */
    private function exportRecoveryCode(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'user_id',
            'recovery',
            'used',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('recovery_codes', $foreignKey, $columns, $data);
    }

    /**
     * Export the Relationship Type Group table.
     *
     * @param  array  $data
     */
    private function exportRelationTypeGroup(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'name',
            'delible',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('relationship_type_groups', $foreignKey, $columns, $data);
    }

    /**
     * Export the Relationship Type table.
     *
     * @param  array  $data
     */
    private function exportRelationType(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'name',
            'name_reverse_relationship',
            'relationship_type_group_id',
            'delible',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('relationship_types', $foreignKey, $columns, $data);
    }

    /**
     * Export the Relationship.
     *
     * @param  array  $data
     */
    private function exportRelationship(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'relationship_type_id',
            'contact_is',
            'of_contact',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('relationships', $foreignKey, $columns, $data);
    }

    /**
     * Export the Reminder Outbox table.
     *
     * @param  array  $data
     */
    private function exportReminderOutbox(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'reminder_id',
            'user_id',
            'planned_date',
            'nature',
            'notification_number_days_before',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('reminder_outbox', $foreignKey, $columns, $data);
    }

    /**
     * Export the Reminder Rule table.
     *
     * @param  array  $data
     */
    private function exportReminderRule(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'number_of_days_before',
            'active',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('reminder_rules', $foreignKey, $columns, $data);
    }

    /**
     * Export the Reminder table.
     *
     * @param  array  $data
     */
    private function exportReminder(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'initial_date',
            'title',
            'description',
            'frequency_type',
            'frequency_number',
            'delible',
            'inactive',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('reminders', $foreignKey, $columns, $data);
    }

    /**
     * Export the Special Date table.
     *
     * @param  array  $data
     */
    private function exportSpecialDate(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'uuid',
            'is_age_based',
            'is_year_unknown',
            'date',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('special_dates', $foreignKey, $columns, $data);
    }

    /**
     * Export the Tag table.
     *
     * @param  array  $data
     */
    private function exportTag(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'name',
            'name_slug',
            'description',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('tags', $foreignKey, $columns, $data);
    }

    /**
     * Export the Task table.
     *
     * @param  array  $data
     */
    private function exportTask(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'contact_id',
            'uuid',
            'title',
            'description',
            'completed',
            'completed_at',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('tasks', $foreignKey, $columns, $data);
    }

    /**
     * Export the Term User table.
     *
     * @param  array  $data
     */
    private function exportTermUser(array $data)
    {
        $columns = [
            'account_id',
            'user_id',
            'term_id',
            'ip_address',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('term_user', $foreignKey, $columns, $data);
    }

    /**
     * Export the User table.
     *
     * @param  array  $data
     */
    private function exportUser(array $data)
    {
        $columns = [
            'id',
            'first_name',
            'last_name',
            'email',
            'me_contact_id',
            'admin',
            'email_verified_at',
            'password',
            'remember_token',
            'google2fa_secret',
            'account_id',
            'timezone',
            'currency_id',
            'locale',
            'metric',
            'fluid_container',
            'contacts_sort_order',
            'name_order',
            'invited_by_user_id',
            'dashboard_active_tab',
            'gifts_active_tab',
            'profile_active_tab',
            'profile_new_life_event_badge_seen',
            'temperature_scale',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('users', $foreignKey, $columns, $data);
    }

    /**
     * Export the Weather table.
     *
     * @param  array  $data
     */
    private function exportWeather(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'place_id',
            'weather_json',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('weather', $foreignKey, $columns, $data);
    }

    /**
     * Export the Contact Photo table.
     * This is custom as we need to loop on the contacts for this account.
     *
     * @param  array  $data
     */
    private function exportContactPhoto(array $data)
    {
        $contacts = DB::table('contacts')
            ->select('id')
            ->where('account_id', $data['account_id'])
            ->get();

        if ($contacts->count() == 0) {
            return;
        }

        $sql = 'INSERT IGNORE INTO '.DBHelper::getTable('contact_photo').' (`contact_id`, `photo_id`, `created_at`, `updated_at`) VALUES'.PHP_EOL;
        $insertValues = [];
        foreach ($contacts as $contact) {
            $photos = DB::table('contact_photo')
                ->where('contact_id', $contact->id)
                ->get();

            foreach ($photos as $photo) {
                array_push($insertValues, ' ('.$photo->contact_id.','.$photo->photo_id.",'".$photo->created_at."','".$photo->updated_at."')");
            }
        }
        $sql .= implode(','.PHP_EOL, $insertValues);
        $this->writeToTempFile($sql.';'.PHP_EOL);
    }

    /**
     * Export the Audit logs table.
     *
     * @param  array  $data
     */
    private function exportAuditLogs(array $data)
    {
        $columns = [
            'id',
            'account_id',
            'author_id',
            'about_contact_id',
            'author_name',
            'action',
            'objects',
            'should_appear_on_dashboard',
            'audited_at',
            'created_at',
            'updated_at',
        ];

        $foreignKey = 'account_id';

        $this->buildInsertSQLQuery('audit_logs', $foreignKey, $columns, $data);
    }
}
