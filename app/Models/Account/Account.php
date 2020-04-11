<?php

namespace App\Models\Account;

use App\Models\User\User;
use App\Models\Contact\Tag;
use App\Models\Journal\Day;
use App\Models\User\Module;
use Illuminate\Support\Str;
use App\Models\Contact\Call;
use App\Models\Contact\Debt;
use App\Models\Contact\Gift;
use App\Models\Contact\Note;
use App\Models\Contact\Task;
use App\Traits\Subscription;
use App\Models\Journal\Entry;
use App\Models\Contact\Gender;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Document;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use App\Models\Instance\AuditLog;
use App\Services\User\CreateUser;
use App\Models\Contact\Occupation;
use Illuminate\Support\Facades\DB;
use App\Models\Contact\ContactField;
use App\Models\Contact\Conversation;
use App\Models\Contact\ReminderRule;
use App\Models\Instance\SpecialDate;
use App\Models\Journal\JournalEntry;
use App\Models\Contact\LifeEventType;
use App\Models\Contact\ReminderOutbox;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contact\ContactFieldType;
use App\Models\Contact\LifeEventCategory;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\Auth\Population\PopulateModulesTable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Auth\Population\PopulateLifeEventsTable;
use App\Services\Auth\Population\PopulateContactFieldTypesTable;

/**
 * @property int $reminders_count
 * @property int $notes_count
 * @property int $activities_count
 * @property int $gifts_count
 * @property int $tasks_count
 */
class Account extends Model
{
    use Subscription;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number_of_invitations_sent',
        'api_key',
        'default_time_reminder_is_sent',
        'default_gender_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'has_access_to_paid_version_for_free' => 'boolean',
    ];

    /**
     * Get the activity records associated with the account.
     *
     * @return HasMany
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the contact records associated with the account.
     *
     * @return HasMany
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the invitations associated with the account.
     *
     * @return HasMany
     */
    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Get the debt records associated with the account.
     *
     * @return HasMany
     */
    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    /**
     * Get the gift records associated with the account.
     *
     * @return HasMany
     */
    public function gifts()
    {
        return $this->hasMany(Gift::class);
    }

    /**
     * Get the note records associated with the account.
     *
     * @return HasMany
     */
    public function notes()
    {
        return $this->hasMany(Note::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the reminder records associated with the account.
     *
     * @return HasMany
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get the reminder outboxes records associated with the account.
     *
     * @return HasMany
     */
    public function reminderOutboxes()
    {
        return $this->hasMany(ReminderOutbox::class);
    }

    /**
     * Get the task records associated with the account.
     *
     * @return HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the user records associated with the account.
     *
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the relationship records associated with the account.
     *
     * @return HasMany
     */
    public function relationships()
    {
        return $this->hasMany(Relationship::class);
    }

    /**
     * Get the activity statistics record associated with the account.
     *
     * @return HasMany
     */
    public function activityStatistics()
    {
        return $this->hasMany(ActivityStatistic::class);
    }

    /**
     * Get the activity type records associated with the account.
     *
     * @return HasMany
     */
    public function activityTypes()
    {
        return $this->hasMany(ActivityType::class);
    }

    /**
     * Get the activity type category records associated with the account.
     *
     * @return HasMany
     */
    public function activityTypeCategories()
    {
        return $this->hasMany(ActivityTypeCategory::class);
    }

    /**
     * Get the task records associated with the account.
     *
     * @return HasMany
     */
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * Get the import jobs records associated with the account.
     *
     * @return HasMany
     */
    public function importjobs()
    {
        return $this->hasMany(ImportJob::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the import job reports records associated with the account.
     *
     * @return HasMany
     */
    public function importJobReports()
    {
        return $this->hasMany(ImportJobReport::class);
    }

    /**
     * Get the tags records associated with the account.
     *
     * @return HasMany
     */
    public function tags()
    {
        return $this->hasMany(Tag::class)->orderBy('name', 'asc');
    }

    /**
     * Get the calls records associated with the account.
     *
     * @return HasMany
     */
    public function calls()
    {
        return $this->hasMany(Call::class)->orderBy('called_at', 'desc');
    }

    /**
     * Get the Contact Field types records associated with the account.
     *
     * @return HasMany
     */
    public function contactFieldTypes()
    {
        return $this->hasMany(ContactFieldType::class);
    }

    /**
     * Get the Contact Field records associated with the contact.
     *
     * @return HasMany
     */
    public function contactFields()
    {
        return $this->hasMany(ContactField::class);
    }

    /**
     * Get the Journal Entries records associated with the account.
     *
     * @return HasMany
     */
    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class)->orderBy('date', 'desc');
    }

    /**
     * Get the special dates records associated with the account.
     *
     * @return HasMany
     */
    public function specialDates()
    {
        return $this->hasMany(SpecialDate::class);
    }

    /**
     * Get the Days records associated with the account.
     *
     * @return HasMany
     */
    public function days()
    {
        return $this->hasMany(Day::class);
    }

    /**
     * Get the Genders records associated with the account.
     *
     * @return HasMany
     */
    public function genders()
    {
        return $this->hasMany(Gender::class);
    }

    /**
     * Get the Reminder Rules records associated with the account.
     *
     * @return HasMany
     */
    public function reminderRules()
    {
        return $this->hasMany(ReminderRule::class);
    }

    /**
     * Get the relationship types records associated with the account.
     *
     * @return HasMany
     */
    public function relationshipTypes()
    {
        return $this->hasMany(RelationshipType::class);
    }

    /**
     * Get the relationship type groups records associated with the account.
     *
     * @return HasMany
     */
    public function relationshipTypeGroups()
    {
        return $this->hasMany(RelationshipTypeGroup::class);
    }

    /**
     * Get the modules records associated with the account.
     *
     * @return HasMany
     */
    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    /**
     * Get the Conversation records associated with the account.
     *
     * @return HasMany
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the Message records associated with the account.
     *
     * @return HasMany
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the Document records associated with the account.
     *
     * @return HasMany
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the Life Event Category records associated with the account.
     *
     * @return HasMany
     */
    public function lifeEventCategories()
    {
        return $this->hasMany(LifeEventCategory::class);
    }

    /**
     * Get the Life Event Type records associated with the account.
     *
     * @return HasMany
     */
    public function lifeEventTypes()
    {
        return $this->hasMany(LifeEventType::class);
    }

    /**
     * Get the Life Event records associated with the account.
     *
     * @return HasMany
     */
    public function lifeEvents()
    {
        return $this->hasMany(LifeEvent::class);
    }

    /**
     * Get the Photos records associated with the account.
     *
     * @return HasMany
     */
    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * Get the Weather records associated with the account.
     *
     * @return HasMany
     */
    public function weathers()
    {
        return $this->hasMany(Weather::class);
    }

    /**
     * Get the Places records associated with the account.
     *
     * @return HasMany
     */
    public function places()
    {
        return $this->hasMany(Place::class);
    }

    /**
     * Get the Addresses records associated with the account.
     *
     * @return HasMany
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the Company records associated with the account.
     *
     * @return HasMany
     */
    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Get the Occupation records associated with the account.
     *
     * @return HasMany
     */
    public function occupations()
    {
        return $this->hasMany(Occupation::class);
    }

    /**
     * * Get the Audit log records associated with the account.
     *
     * @return HasMany
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Populates the Activity Type table right after an account is
     * created.
     */
    public function populateActivityTypeTable()
    {
        $defaultActivityTypeCategories = DB::table('default_activity_type_categories')->get();

        foreach ($defaultActivityTypeCategories as $defaultActivityTypeCategory) {
            $activityTypeCategoryId = DB::table('activity_type_categories')->insertGetId([
                'account_id' => $this->id,
                'translation_key' => $defaultActivityTypeCategory->translation_key,
            ]);

            $defaultActivityTypes = DB::table('default_activity_types')
                ->where('default_activity_type_category_id', $defaultActivityTypeCategory->id)
                ->get();

            foreach ($defaultActivityTypes as $defaultActivityType) {
                DB::table('activity_types')->insert([
                    'account_id' => $this->id,
                    'activity_type_category_id' => $activityTypeCategoryId,
                    'translation_key' => $defaultActivityType->translation_key,
                ]);
            }
        }
    }

    /**
     * Populates the default genders in a new account.
     *
     * @return void
     */
    public function populateDefaultGendersTable()
    {
        Gender::create(['type' => Gender::MALE, 'name' => trans('app.gender_male'), 'account_id' => $this->id]);
        Gender::create(['type' => Gender::FEMALE, 'name' => trans('app.gender_female'), 'account_id' => $this->id]);
        Gender::create(['type' => Gender::OTHER, 'name' => trans('app.gender_none'), 'account_id' => $this->id]);
    }

    /**
     * Populates the default reminder rules in a new account.
     *
     * @return void
     */
    public function populateDefaultReminderRulesTable()
    {
        ReminderRule::create(['number_of_days_before' => 7, 'account_id' => $this->id, 'active' => 1]);
        ReminderRule::create(['number_of_days_before' => 30, 'account_id' => $this->id, 'active' => 1]);
    }

    /**
     * Populates the default relationship types in a new account.
     *
     * @return void
     */
    public function populateRelationshipTypeGroupsTable($ignoreTableAlreadyMigrated = false)
    {
        $defaultRelationshipTypeGroups = DB::table('default_relationship_type_groups')->get();
        foreach ($defaultRelationshipTypeGroups as $defaultRelationshipTypeGroup) {
            if (! $ignoreTableAlreadyMigrated || $defaultRelationshipTypeGroup->migrated == 0) {
                DB::table('relationship_type_groups')->insert([
                    'account_id' => $this->id,
                    'name' => $defaultRelationshipTypeGroup->name,
                    'delible' => $defaultRelationshipTypeGroup->delible,
                ]);
            }
        }
    }

    /**
     * Populate the relationship types table based on the default ones.
     *
     * @return void
     */
    public function populateRelationshipTypesTable($migrateOnlyNewTypes = false)
    {
        if ($migrateOnlyNewTypes) {
            $defaultRelationshipTypes = DB::table('default_relationship_types')->where('migrated', 0)->get();
        } else {
            $defaultRelationshipTypes = DB::table('default_relationship_types')->get();
        }

        foreach ($defaultRelationshipTypes as $defaultRelationshipType) {
            $defaultRelationshipTypeGroup = DB::table('default_relationship_type_groups')
                ->where('id', $defaultRelationshipType->relationship_type_group_id)
                ->first();

            $relationshipTypeGroup = $this->getRelationshipTypeGroupByType($defaultRelationshipTypeGroup->name);

            if ($relationshipTypeGroup) {
                RelationshipType::create([
                    'account_id' => $this->id,
                    'name' => $defaultRelationshipType->name,
                    'name_reverse_relationship' => $defaultRelationshipType->name_reverse_relationship,
                    'relationship_type_group_id' => $relationshipTypeGroup->id,
                    'delible' => $defaultRelationshipType->delible,
                ]);
            }
        }
    }

    /**
     * Create a new account and associate a new User.
     *
     * @param string $first_name
     * @param string $last_name
     * @param string $email
     * @param string $password
     * @param string $ipAddress
     * @return self
     */
    public static function createDefault($first_name, $last_name, $email, $password, $ipAddress = null, $lang = null)
    {
        // create new account
        $account = new self;
        $account->api_key = Str::random(30);
        $account->created_at = now();
        $account->save();

        try {
            // create the first user for this account
            $user = app(CreateUser::class)->execute([
                'account_id' => $account->id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => $password,
                'locale' => $lang,
                'ip_address' => $ipAddress,
            ]);
        } catch (\Exception $e) {
            $account->delete();
            throw $e;
        }

        $account->populateDefaultFields();

        return $account;
    }

    /**
     * Populates all the default column that should be there when a new account
     * is created or reset.
     */
    public function populateDefaultFields()
    {
        app(PopulateContactFieldTypesTable::class)->execute([
            'account_id' => $this->id,
            'migrate_existing_data' => true,
        ]);

        $this->populateDefaultGendersTable();
        $this->populateDefaultReminderRulesTable();
        $this->populateRelationshipTypeGroupsTable();
        $this->populateRelationshipTypesTable();
        $this->populateActivityTypeTable();

        app(PopulateLifeEventsTable::class)->execute([
            'account_id' => $this->id,
            'migrate_existing_data' => true,
        ]);

        app(PopulateModulesTable::class)->execute([
            'account_id' => $this->id,
            'migrate_existing_data' => true,
        ]);
    }

    /**
     * Gets the RelationshipType object matching the given type.
     *
     * @param  string $relationshipTypeName
     * @return RelationshipType|null
     */
    public function getRelationshipTypeByType(string $relationshipTypeName)
    {
        return $this->relationshipTypes->where('name', $relationshipTypeName)->first();
    }

    /**
     * Gets the RelationshipType object matching the given type.
     *
     * @param  string $relationshipTypeGroupName
     * @return RelationshipTypeGroup|null
     */
    public function getRelationshipTypeGroupByType(string $relationshipTypeGroupName)
    {
        return $this->relationshipTypeGroups->where('name', $relationshipTypeGroupName)->first();
    }

    /**
     * Get the first available locale in an account. This gets the first user
     * in the account and reads his locale.
     *
     * @return string|null
     *
     * @throws ModelNotFoundException
     */
    public function getFirstLocale(): ?string
    {
        try {
            $user = $this->users()->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return null;
        }

        return $user->locale;
    }
}
