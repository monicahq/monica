<?php

namespace App;

use Laravel\Cashier\Billable;
use App\Jobs\AddChangelogEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Get the event records associated with the account.
     *
     * @return HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class)->orderBy('created_at', 'desc');
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
     * Get the progenitor records associated with the account.
     *
     * @return HasMany
     */
    public function progenitors()
    {
        return $this->hasMany(Progenitor::class);
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
        return $this->hasMany('App\Tag')->orderBy('name', 'asc');
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
        return $this->hasMany('App\ContactFieldType');
    }

    /**
     * Get the Contact Field records associated with the contact.
     *
     * @return HasMany
     */
    public function contactFields()
    {
        return $this->hasMany('App\ContactField');
    }

    /**
     * Get the Journal Entries records associated with the account.
     *
     * @return HasMany
     */
    public function journalEntries()
    {
        return $this->hasMany('App\JournalEntry')->orderBy('date', 'desc');
    }

    /**
     * Get the Days records associated with the account.
     *
     * @return HasMany
     */
    public function days()
    {
        return $this->hasMany('App\Day');
    }

    /**
     * Get the Genders records associated with the account.
     *
     * @return HasMany
     */
    public function genders()
    {
        return $this->hasMany('App\Gender');
    }

    /**
     * Get the Reminder Rules records associated with the account.
     *
     * @return HasMany
     */
    public function reminderRules()
    {
        return $this->hasMany('App\ReminderRule');
    }

    /**
     * Get the relationship types records associated with the account.
     *
     * @return HasMany
     */
    public function relationshipTypes()
    {
        return $this->hasMany('App\RelationshipType');
    }

    /**
     * Get the relationship type groups records associated with the account.
     *
     * @return HasMany
     */
    public function relationshipTypeGroups()
    {
        return $this->hasMany('App\RelationshipTypeGroup');
    }

    /**
     * Get the modules records associated with the account.
     *
     * @return HasMany
     */
    public function modules()
    {
        return $this->hasMany('App\Module');
    }

    /**
     * Get the Notifications records associated with the account.
     *
     * @return HasMany
     */
    public function notifications()
    {
        return $this->hasMany('App\Notification');
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

        // number of users in the account should be == 1
        if ($numberOfUsers > 1) {
            $canDowngrade = false;
        }

        // there should not be any pending user invitations
        if ($numberPendingInvitations > 0) {
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
        $timestamp = $this->asStripeCustomer()['subscriptions']
                            ->data[0]['current_period_end'];

        return \App\Helpers\DateHelper::getShortDate($timestamp);
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
     * @param  bool $ignoreTableAlreadyMigrated
     * @return void
     */
    public function populateRelationshipTypesTable($ignoreTableAlreadyMigrated = false)
    {
        $defaultRelationshipTypes = DB::table('default_relationship_types')->get();

        foreach ($defaultRelationshipTypes as $defaultRelationshipType) {
            if (! $ignoreTableAlreadyMigrated || $defaultRelationshipType->migrated == 0) {
                $defaultRelationshipTypeGroup = DB::table('default_relationship_type_groups')
                                        ->where('id', $defaultRelationshipType->relationship_type_group_id)
                                        ->first();

                $relationshipTypeGroup = $this->getRelationshipTypeGroupByType($defaultRelationshipTypeGroup->name);

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
     * Populate the account modules table based on the default ones.
     *
     * @param  bool $ignoreTableAlreadyMigrated
     * @return void
     */
    public function populateModulesTable($ignoreTableAlreadyMigrated = false)
    {
        $defaultModules = DB::table('default_contact_modules')->get();

        foreach ($defaultModules as $defaultModule) {
            if (! $ignoreTableAlreadyMigrated || $defaultModule->migrated == 0) {
                Module::create([
                    'account_id' => $this->id,
                    'key' => $defaultModule->key,
                    'translation_key' => $defaultModule->translation_key,
                    'delible' => $defaultModule->delible,
                    'active' => $defaultModule->active,
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
        $startOfMonth = now()->addMonthsNoOverflow($month)->startOfMonth();
        $endInThreeMonths = now()->addMonthsNoOverflow($month)->endOfMonth();

        return auth()->user()->account->reminders()
                     ->whereBetween('next_expected_date', [$startOfMonth, $endInThreeMonths])
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

        return $plan->stripe_plan;
    }

    /**
     * Get the friendly name of the plan the account is subscribed to.
     *
     * @return string
     */
    public function getSubscribedPlanName()
    {
        $plan = $this->subscriptions()->first();

        return $plan->name;
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
     * @return $this
     */
    public static function createDefault($first_name, $last_name, $email, $password)
    {
        // create new account
        $account = new self;
        $account->api_key = str_random(30);
        $account->created_at = now();
        $account->save();

        $account->populateDefaultFields($account);

        // create the first user for this account
        User::createDefault($account->id, $first_name, $last_name, $email, $password);

        return $account;
    }

    /**
     * Populates all the default column that should be there when a new account
     * is created or reset.
     */
    public static function populateDefaultFields($account)
    {
        $account->populateContactFieldTypeTable();
        $account->populateDefaultGendersTable();
        $account->populateDefaultReminderRulesTable();
        $account->populateRelationshipTypeGroupsTable();
        $account->populateRelationshipTypesTable();
        $account->populateModulesTable();
        $account->populateChangelogsTable();
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
     * Add the given changelog entry and mark it unread for all users in this
     * account.
     *
     * @param int $changelogId
     */
    public function addUnreadChangelogEntry(int $changelogId)
    {
        foreach ($this->users as $user) {
            $user->changelogs()->syncWithoutDetaching([$changelogId => ['read' => 0]]);
        }
    }

    /**
     * Populate the changelog_user table, which contains all the new changes
     * made on the application.
     *
     * @return void
     */
    public function populateChangelogsTable()
    {
        $changelogs = \App\Changelog::all();
        foreach ($changelogs as $changelog) {
            AddChangelogEntry::dispatch($this, $changelog->id);
        }
    }
}
