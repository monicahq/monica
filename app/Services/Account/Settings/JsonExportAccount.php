<?php

namespace App\Services\Account\Settings;

use App\Models\User\User;
use Illuminate\Support\Str;
use App\Services\BaseService;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class JsonExportAccount extends BaseService
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
     * Export account as Json.
     *
     * @param array $data
     * @return string
     */
    public function execute(array $data): string
    {
        $this->validate($data);

        $user = User::findOrFail($data['user_id']);

        $this->tempFileName = 'temp/'.Str::random(40).'.json';

        $this->writeExport($data, $user);

        return $this->tempFileName;
    }

    /**
     * Export data in temp file.
     *
     * @param array $data
     * @param User $user
     */
    private function writeExport(array $data, User $user)
    {
        $result = new class {};
        $result->account = $this->exportAccount($data);

        // $this->exportAddress($data);
        // $this->exportCompany($data);

        // $this->exportContactFieldType($data); // config

        // $this->exportConversation($data);
        // $this->exportDays($data);
        // $this->exportEmotionCall($data);
        // $this->exportEntries($data);
        // $this->exportInvitation($data);
        // $this->exportJournalEntry($data);
        // $this->exportLifeEventCategory($data);
        // $this->exportLifeEventType($data);
        // $this->exportLifeEvent($data);
        // $this->exportMessage($data);
        // $this->exportMetaDataLoveRelationship($data);
        // $this->exportModule($data);
        // $this->exportNote($data);
        // $this->exportOccupation($data);
        // $this->exportPet($data);
        // $this->exportPlace($data);
        // $this->exportRecoveryCode($data);
        // $this->exportRelationTypeGroup($data);
        // $this->exportRelationType($data);
        // $this->exportRelationship($data);
        // $this->exportReminderOutbox($data);
        // $this->exportReminderRule($data);
        // $this->exportReminderSent($data);
        // $this->exportReminder($data);
        // $this->exportSpecialDate($data);
        // $this->exportTag($data);
        // $this->exportTask($data);
        // $this->exportTermUser($data);
        // $this->exportWeather($data);
        // $this->exportContactPhoto($data);
        // $this->exportAuditLogs($data);

        $this->writeToTempFile(json_encode($result, JSON_PRETTY_PRINT));
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
     * @param array $data
     * @return mixed
     */
    private function exportAccount(array $data)
    {
        $account = Account::find($data['account_id']);

        $columns = [
            'uuid',
            'api_key',
        ];

        $properties = [
            'number_of_invitations_sent',
        ];

        return $this->getOneData($account, $columns, $properties, function (object $obj, $account) {
            $obj->users = $this->exportUser($account);
            $obj->contacts = $this->exportContact($account);
            $obj->activities = $this->exportActivity($account);
            $obj->activityTypes = $this->exportActivityType($account);
            $obj->activityTypeCategories = $this->exportActivityTypeCategory($account);
        });
    }

    /**
     * Export the User table.
     *
     * @param array $data
     */
    private function exportUser(Account $account)
    {
        $columns = [
            'uuid',
            'first_name',
            'last_name',
            'email',
            'email_verified_at',
            'password',
            'google2fa_secret',
            'created_at',
            'updated_at',
        ];

        $properties = [
            'admin',
            'locale',
            'metric',
            'fluid_container',
            'contacts_sort_order',
            'name_order',
            'dashboard_active_tab',
            'gifts_active_tab',
            'profile_active_tab',
            'timezone',
            'profile_new_life_event_badge_seen',
            'temperature_scale',
        ];

        return $this->getData($account->users, $columns, $properties, function (object $obj, $user) {
            $obj->properties['currency'] = $user->currency !== null ? $user->currency->iso : null;

            $invited_by_user = Contact::where('account_id', $user->account_id)->find($user->invited_by_user_id);
            $obj->properties['invited_by_user'] = $invited_by_user !== null ? $invited_by_user->uuid : null;

            $obj->properties['me_contact'] = $user->me !== null ? $user->me->uuid : null;
        });
    }

    /**
     * Export the Contact table.
     *
     * @param array $data
     */
    private function exportContact(Account $account)
    {
        $columns = [
            'uuid',
            // 'avatar_source',
            // 'avatar_gravatar_url',
            // 'avatar_adorable_uuid',
            // 'avatar_adorable_url',
            // 'avatar_default_url',
            // 'avatar_photo_id',
            // 'has_avatar',
            // 'avatar_external_url',
            // 'avatar_file_name',
            // 'avatar_location',
            // 'gravatar_url',
            // 'default_avatar_color',
            // 'has_avatar_bool',
            'created_at',
            'updated_at',
        ];

        $properties = [
            'first_name',
            'middle_name',
            'last_name',
            'nickname',
            'description',
            'is_starred',
            'is_partial',
            'is_active',
            'is_dead',
            'job',
            'company',
            'food_preferences',
            'last_talked_to',
            'last_consulted_at',
            'number_of_views',
        ];

        return $this->getData($account->contacts, $columns, $properties, function (object $obj, Contact $contact) {
            // $obj->properties['avatar'] = null;

            $obj->properties['tags'] = $contact->getTagsAsString();
            //$this->setComplexProperty($obj, 'gender', $contact, ['type', 'name']);
            $obj->properties['gender'] = $contact->gender->type;
            $this->setComplexProperty($obj, 'deceased_date', $contact, self::$specialDateColumns, 'specialDate', 'deceasedDate');
            $this->setComplexProperty($obj, 'deceased_reminder', $contact, self::$reminderColumns, 'reminder', 'deceased_reminder_id');

            $this->exportContactDebts($contact, $obj);
            $this->exportContactActivities($contact, $obj);
            $this->exportContactCalls($contact, $obj);
            $this->exportContactContactFields($contact, $obj);
            $this->exportContactGifts($contact, $obj);
            $this->exportContactPhotos($contact, $obj);
            $this->exportContactDocuments($contact, $obj);
        });
    }

    private function exportContactDebts(Contact $contact, $obj)
    {
        $debts = $this->getData($contact->debts, ['in_debt', 'status', 'amount'], ['currency'], function (object $obj, $debt) {
            $obj->in_debt = $obj->in_debt === 'yes';
        });
        if ($debts !== null) {
            $obj->properties['debts'] = $debts;
        }
    }

    private function exportContactActivities(Contact $contact, $obj)
    {
        $activities = $this->getData($contact->activities, ['uuid']);
        if ($activities !== null) {
            $activities->values = array_map(function ($x) {
                return $x->uuid;
            }, $activities->values);
            $obj->properties['activities'] = $activities;
        }
    }

    private function exportContactCalls(Contact $contact, $obj)
    {
        $calls = $this->getData($contact->calls, ['created_at', 'updated_at'], ['called_at', 'content', 'contact_called'], function (object $obj, $call) {
            $obj->properties['emotions'] = $this->getData($call->emotions, ['name']);
        });
        if ($calls !== null) {
            $obj->properties['calls'] = $calls;
        }
    }

    private function exportContactContactFields(Contact $contact, $obj)
    {
        $contactFields = $this->getData($contact->contactFields, ['created_at', 'updated_at'], ['data'], function (object $obj, $contactField) {
            $type = $this->getOneData($contactField->contactFieldType, ['uuid']);
            $obj->properties['type'] = $type->uuid;
        });
        if ($contactFields !== null) {
            $obj->properties['contact_fields'] = $contactFields;
        }
    }

    private function exportContactGifts(Contact $contact, $obj)
    {
        $gifts = $this->getData($contact->gifts, ['created_at', 'updated_at'], ['name', 'comment', 'url', 'amount', 'status', 'date'], function (object $obj, $gift) {
            if ($gift->recipient) {
                $obj->properties['recipient'] = $this->getData($gift->recipient, ['uuid']);
            }
        });
        if ($gifts !== null) {
            $obj->properties['gifts'] = $gifts;
        }
    }

    private function exportContactPhotos(Contact $contact, $obj)
    {
        $photos = $this->getData($contact->photos, ['created_at', 'updated_at'], ['uuid', 'original_filename', 'filesize', 'mime_type'], function (object $obj, $photo) {
            $dataUrl = $photo->dataUrl();
            if ($dataUrl) {
                $obj->properties['dataUrl'] = $dataUrl;
            }
        });
        if ($photos !== null) {
            $obj->properties['photos'] = $photos;
        }
    }

    private function exportContactDocuments(Contact $contact, $obj)
    {
        $documents = $this->getData($contact->documents, ['created_at', 'updated_at'], ['uuid', 'original_filename', 'filesize', 'type', 'mime_type', 'number_of_downloads'], function (object $obj, $document) {
            $dataUrl = $document->dataUrl();
            if ($dataUrl) {
                $obj->properties['dataUrl'] = $dataUrl;
            }
        });
        if ($documents !== null) {
            $obj->properties['documents'] = $documents;
        }
    }

    /**
     * Export the Activity table.
     *
     * @param array $data
     */
    private function exportActivity(Account $account)
    {
        $columns = [
            'uuid',
            'created_at',
            'updated_at',
        ];

        $properties = [
            'summary',
            'description',
            'happened_at',
        ];

        return $this->getData($account->activities, $columns, $properties, function (object $obj, $activity) {
            $this->setSimpleProperty($obj, 'type', $activity->type, 'uuid', 'uuid');
        });
    }

    /**
     * Export the Activity table.
     *
     * @param array $data
     */
    private function exportActivityType(Account $account)
    {
        $columns = [
            'uuid',
            'created_at',
            'updated_at',
        ];

        $properties = [
            'name',
            'translation_key',
            'location_type',
        ];

        return $this->getData($account->activityTypes, $columns, $properties, function (object $obj, $activityType) {
            $this->setSimpleProperty($obj, 'category', $activityType->category, 'uuid', 'uuid');
        });
    }

    /**
     * Export the Activity table.
     *
     * @param array $data
     */
    private function exportActivityTypeCategory(Account $account)
    {
        $columns = [
            'uuid',
            'created_at',
            'updated_at',
        ];

        $properties = [
            'name',
            'translation_key',
        ];

        return $this->getData($account->activityTypeCategories, $columns, $properties);
    }

    private static $specialDateColumns = [
        'uuid' => 'string',
        'is_age_based' => 'bool',
        'is_year_unknown' => 'bool',
        'date' => 'date',
        'created_at' => 'date',
        'updated_at' => 'date',
    ];

    private static $reminderColumns = [
        'initial_date' => 'date',
        'title' => 'string',
        'description' => 'string',
        'frequency_type' => 'string',
        'frequency_number' => 'number',
        'delible' => 'bool',
        'inactive' => 'bool',
        'created_at' => 'date',
        'updated_at' => 'date',
    ];

    /**
     * Create the Insert query for the given table.
     *
     * @param string $tableName
     * @param array $foreignKey
     * @param array $columns
     * @return mixed
     */
    private function getData($data, array $columns, array $properties = null, callable $callback = null)
    {
        $result = new class {};
        $values = [];

        if ($data->count() == 0) {
            return null;
        }

        foreach ($data as $singleData) {
            $values[] = $this->getOneData($singleData, $columns, $properties, $callback);
        }

        $result->count = count($values);
        $result->values = $values;

        return $result;
    }

    /**
     * Create the Insert query for the given table.
     *
     * @param string $tableName
     * @param array $foreignKey
     * @param array $columns
     * @return mixed
     */
    private function getOneData(Model $model, array $columns, array $properties = null, callable $callback = null)
    {
        $result = new class {};

        if (! $model->exists()) {
            return null;
        }

        foreach ($columns as $column) {
            $result->{$column} = $model->{$column} ?? $model->getAttributeValue($column);
        }

        if ($properties != null) {
            $result->properties = [];
            foreach ($properties as $property) {
                $this->setSimpleProperty($result, $property, $model);
            }
        }
        if ($callback != null) {
            if (! isset($result->properties)) {
                $result->properties = [];
            }
            $callback($result, $model);
        }

        return $result;
    }

    private function setSimpleProperty(object $obj, string $name, ?Model $model, ?string $prop = null): bool
    {
        if ($model === null || ! $model->exists()) {
            return false;
        }

        $prop = $prop ?? $name;

        $value = $model->{$prop} ?? $model->getAttributeValue($prop);
        if ($value === null) {
            return false;
        }

        $obj->properties[$name] = $value;

        return true;
    }

    private function setComplexProperty(object $obj, string $name, ?Model $model, array $values, ?string $type = 'array', ?string $prop = null)
    {
        if ($model === null) {
            return false;
        }

        $prop = $prop ?? $name;

        $result = new class {};
        $result->type = $type ?? 'array';
        $result->properties = [];

        $data = $model->{$prop} ?? $model->getAttributeValue($prop);
        if ($data === null) {
            return false;
        }

        foreach ($values as $value => $type) {
            if (is_int($value)) {
                $value = $type;
                $type = null;
            }
            $this->setSimpleProperty($result, $value, $data, $type);
        }

        $obj->properties[$name] = $result;

        return true;
    }
}
