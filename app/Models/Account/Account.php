<?php

namespace App\Models\Account;

use App\Models\User\User;
use App\Helpers\DateHelper;
use App\Models\Contact\Tag;
use App\Models\Journal\Day;
use App\Models\User\Module;
use App\Models\Contact\Call;
use App\Models\Contact\Debt;
use App\Models\Contact\Gift;
use App\Models\Contact\Note;
use App\Models\Contact\Task;
use App\Models\Journal\Entry;
use Laravel\Cashier\Billable;
use App\Models\Contact\Gender;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Activity;
use App\Models\Contact\Document;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use Illuminate\Support\Facades\DB;
use App\Models\Contact\ActivityType;
use App\Models\Contact\ContactField;
use App\Models\Contact\Conversation;
use App\Models\Contact\Notification;
use App\Models\Contact\ReminderRule;
use App\Models\Instance\SpecialDate;
use App\Models\Journal\JournalEntry;
use App\Models\Contact\LifeEventType;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contact\ContactFieldType;
use App\Models\Contact\ActivityStatistic;
use App\Models\Contact\LifeEventCategory;
use App\Models\Relationship\Relationship;
use App\Models\Contact\ActivityTypeCategory;
use App\Models\Relationship\RelationshipType;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\Auth\Population\PopulateModulesTable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Auth\Population\PopulateLifeEventsTable;

class Account extends Model
{
    use Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number_of_invitations_sent',
        'api_key',
        'default_time_reminder_is_sent',
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
     * Get the Notifications records associated with the account.
     *
     * @return HasMany
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
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
     * Get the default time reminder is sent.
     *
     * @param  string  $value
     * @return string
     */
    public function getDefaultTimeReminderIsSentAttribute($value)
    {
        return $value;
    }

    /**
     * Set the default time a reminder is sent.
     *
     * @param  string  $value
     * @return void
     */
    public function setDefaultTimeReminderIsSentAttribute($value)
    {
        $this->attributes['default_time_reminder_is_sent'] = $value;
    }

    /**
     * Check if the account can be downgraded, based on a set of rules.
     *
     * @return $this
     */
    public function canDowngrade()
    {
        $canDowngrade = true;
        $numberOfUsers = $this->users()->count();
        $numberPendingInvitations = $this->invitations()->count();
        $numberContacts = $this->contacts()->count();

        // number of users in the account should be == 1
        if ($numberOfUsers > 1) {
            $canDowngrade = false;
        }

        // there should not be any pending user invitations
        if ($numberPendingInvitations > 0) {
            $canDowngrade = false;
        }

        // there should not be more than the number of contacts allowed
        if ($numberContacts > config('monica.number_of_allowed_contacts_free_account')) {
            $canDowngrade = false;
        }

        return $canDowngrade;
    }

    /**
     * Check if the account is currently subscribed to a plan.
     *
     * @return bool $isSubscribed
     */
    public function isSubscribed()
    {
        if ($this->has_access_to_paid_version_for_free) {
            return true;
        }

        $isSubscribed = false;

        if ($this->subscribed(config('monica.paid_plan_monthly_friendly_name'))) {
            $isSubscribed = true;
        }

        if ($this->subscribed(config('monica.paid_plan_annual_friendly_name'))) {
            $isSubscribed = true;
        }

        return $isSubscribed;
    }

    /**
     * Check if the account has invoices linked to this account.
     * This was created because Laravel Cashier doesn't know how to properly
     * handled the case when a user doesn't have invoices yet. This sucks balls.
     *
     * @return bool
     */
    public function hasInvoices()
    {
        $query = DB::table('subscriptions')->where('account_id', $this->id)->count();

        return $query > 0;
    }

    /**
     * Get the next billing date for the account.
     *
     * @return string $timestamp
     */
    public function getNextBillingDate()
    {
        // Weird method to get the next billing date from Laravel Cashier
        // see https://stackoverflow.com/questions/41576568/get-next-billing-date-from-laravel-cashier
        $subscriptions = $this->asStripeCustomer()['subscriptions'];
        if (count($subscriptions->data) <= 0) {
            return;
        }
        $timestamp = $subscriptions->data[0]['current_period_end'];

        return DateHelper::getShortDate($timestamp);
    }

    /**
     * Indicates whether the current account has limitations with her current
     * plan.
     *
     * @return bool
     */
    public function hasLimitations()
    {
        if ($this->has_access_to_paid_version_for_free) {
            return false;
        }

        if (! config('monica.requires_subscription')) {
            return false;
        }

        if ($this->isSubscribed()) {
            return false;
        }

        return true;
    }

    /**
     * Indicate whether an account has reached the contact limit if the account
     * is on a free trial.
     *
     * @return bool
     */
    public function hasReachedContactLimit()
    {
        return $this->contacts->count() >= config('monica.number_of_allowed_contacts_free_account');
    }

    /**
     * Get the timezone of the user. In case an account has multiple timezones,
     * takes the first it finds.
     * @return string
     */
    public function timezone()
    {
        $timezone = '';

        foreach ($this->users as $user) {
            $timezone = $user->timezone;
            break;
        }

        return $timezone;
    }

    /**
     * Populates the Contact Field Types table right after an account is
     * created.
     */
    public function populateContactFieldTypeTable($ignoreTableAlreadyMigrated = false)
    {
        $defaultContactFieldTypes = DB::table('default_contact_field_types')->get();

        foreach ($defaultContactFieldTypes as $defaultContactFieldType) {
            if (! $ignoreTableAlreadyMigrated || $defaultContactFieldType->migrated == 0) {
                ContactFieldType::create([
                    'account_id' => $this->id,
                    'name' => $defaultContactFieldType->name,
                    'fontawesome_icon' => (is_null($defaultContactFieldType->fontawesome_icon) ? null : $defaultContactFieldType->fontawesome_icon),
                    'protocol' => (is_null($defaultContactFieldType->protocol) ? null : $defaultContactFieldType->protocol),
                    'delible' => $defaultContactFieldType->delible,
                    'type' => (is_null($defaultContactFieldType->type) ? null : $defaultContactFieldType->type),
                ]);
            }
        }
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
        Gender::create(['name' => trans('app.gender_male'), 'account_id' => $this->id]);
        Gender::create(['name' => trans('app.gender_female'), 'account_id' => $this->id]);
        Gender::create(['name' => trans('app.gender_none'), 'account_id' => $this->id]);
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
     * Get the reminders for the month given in parameter.
     * - 0 means current month
     * - 1 means month+1
     * - 2 means month+2...
     * @param  int    $month
     */
    public function getRemindersForMonth(int $month)
    {
        $startOfMonth = now(DateHelper::getTimezone())->addMonthsNoOverflow($month)->startOfMonth();
        // don't get reminders for past events:
        if ($startOfMonth->isPast()) {
            $startOfMonth = now(DateHelper::getTimezone());
        }
        $endOfMonth = now(DateHelper::getTimezone())->addMonthsNoOverflow($month)->endOfMonth();

        return $this->reminders()
                     ->whereBetween('next_expected_date', [$startOfMonth, $endOfMonth])
                     ->orderBy('next_expected_date', 'asc')
                     ->get();
    }

    /**
     * Get the id of the plan the account is subscribed to.
     *
     * @return string
     */
    public function getSubscribedPlanId()
    {
        $plan = $this->subscriptions()->first();

        if (! is_null($plan)) {
            return $plan->stripe_plan;
        }
    }

    /**
     * Get the friendly name of the plan the account is subscribed to.
     *
     * @return string
     */
    public function getSubscribedPlanName()
    {
        $plan = $this->subscriptions()->first();

        if (! is_null($plan)) {
            return $plan->name;
        }
    }

    /**
     * Cancel the plan the account is subscribed to.
     */
    public function subscriptionCancel()
    {
        $plan = $this->subscriptions()->first();

        if (! is_null($plan)) {
            return $plan->cancelNow();
        }
    }

    /**
     * Replaces a specific gender of all the contacts in the account with another
     * gender.
     *
     * @param  Gender $genderToDelete
     * @param  Gender $genderToReplaceWith
     * @return bool
     */
    public function replaceGender(Gender $genderToDelete, Gender $genderToReplaceWith)
    {
        Contact::where('account_id', $this->id)
                    ->where('gender_id', $genderToDelete->id)
                    ->update(['gender_id' => $genderToReplaceWith->id]);

        return true;
    }

    /**
     * Get if any account exists on the database.
     *
     * @return bool
     */
    public static function hasAny()
    {
        return DB::table('accounts')->count() > 0;
    }

    /**
     * Create a new account and associate a new User.
     *
     * @param string $first_name
     * @param string $last_name
     * @param string $email
     * @param string $password
     * @param string $ipAddress
     * @return $this
     */
    public static function createDefault($first_name, $last_name, $email, $password, $ipAddress = null, $lang = null)
    {
        // create new account
        $account = new self;
        $account->api_key = str_random(30);
        $account->created_at = now();
        $account->save();

        // create the first user for this account
        User::createDefault($account->id, $first_name, $last_name, $email, $password, $ipAddress, $lang);

        $account->populateDefaultFields();

        return $account;
    }

    /**
     * Populates all the default column that should be there when a new account
     * is created or reset.
     */
    public function populateDefaultFields()
    {
        $this->populateContactFieldTypeTable();
        $this->populateDefaultGendersTable();
        $this->populateDefaultReminderRulesTable();
        $this->populateRelationshipTypeGroupsTable();
        $this->populateRelationshipTypesTable();
        $this->populateActivityTypeTable();

        (new PopulateLifeEventsTable)->execute([
            'account_id' => $this->id,
            'migrate_existing_data' => true,
        ]);

        (new PopulateModulesTable)->execute([
            'account_id' => $this->id,
            'migrate_existing_data' => true,
        ]);
    }

    /**
     * Gets the RelationshipType object matching the given type.
     *
     * @param  string $relationshipTypeName
     * @return RelationshipType
     */
    public function getRelationshipTypeByType(string $relationshipTypeName)
    {
        return $this->relationshipTypes->where('name', $relationshipTypeName)->first();
    }

    /**
     * Gets the RelationshipType object matching the given type.
     *
     * @param  string $relationshipTypeGroupName
     * @return RelationshipTypeGroup
     */
    public function getRelationshipTypeGroupByType(string $relationshipTypeGroupName)
    {
        return $this->relationshipTypeGroups->where('name', $relationshipTypeGroupName)->first();
    }

    /**
     * Get the statistics of the number of calls grouped by year.
     *
     * @return array
     */
    public function getYearlyCallStatistics()
    {
        $callsStatistics = collect([]);
        $calls = $this->calls()->latest('called_at')->get();
        $years = [];

        // Create a table that contains the combo year/number of
        foreach ($calls as $call) {
            $yearStatistic = $call->called_at->format('Y');
            $foundInYear = false;

            foreach ($years as $year => $number) {
                if ($year == $yearStatistic) {
                    $years[$year] = $number + 1;
                    $foundInYear = true;
                }
            }

            if (! $foundInYear) {
                $years[$yearStatistic] = 1;
            }
        }

        foreach ($years as $year => $number) {
            $callsStatistics->put($year, $number);
        }

        return $callsStatistics;
    }

    /**
     * Get the statistics of the number of activities grouped by year.
     *
     * @return array
     */
    public function getYearlyActivitiesStatistics()
    {
        $activitiesStatistics = collect([]);
        $activities = $this->activities()->latest('date_it_happened')->get();
        $years = [];

        // Create a table that contains the combo year/number of
        foreach ($activities as $call) {
            $yearStatistic = $call->date_it_happened->format('Y');
            $foundInYear = false;

            foreach ($years as $year => $number) {
                if ($year == $yearStatistic) {
                    $years[$year] = $number + 1;
                    $foundInYear = true;
                }
            }

            if (! $foundInYear) {
                $years[$yearStatistic] = 1;
            }
        }

        foreach ($years as $year => $number) {
            $activitiesStatistics->put($year, $number);
        }

        return $activitiesStatistics;
    }

    /**
     * Get the first available locale in an account. This gets the first user
     * in the account and reads his locale.
     *
     * @return string
     *
     * @throws ModelNotFoundException
     */
    public function getFirstLocale()
    {
        try {
            $user = $this->users()->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return;
        }

        return $user->locale;
    }

    /**
     * Indicates whether the account has the reached the maximum storage size
     * for document upload.
     *
     * @return bool
     */
    public function hasReachedAccountStorageLimit()
    {
        $documents = Document::with(['contact' => function ($query) {
            $query->where('account_id', $this->id);
        }])->orderBy('created_at', 'desc')->get();

        $currentAccountSize = 0;
        foreach ($documents as $document) {
            $currentAccountSize += $document->filesize;
        }

        return $currentAccountSize > (config('monica.max_storage_size') * 1000000);
    }
}
