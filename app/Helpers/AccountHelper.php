<?php

namespace App\Helpers;

use App\Models\Contact\Gender;
use App\Models\Account\Account;
use Illuminate\Support\Collection;

class AccountHelper
{
    /**
     * Indicates whether the given account has limitations with her current
     * plan.
     *
     * @return bool
     */
    public static function hasLimitations(Account $account): bool
    {
        if ($account->has_access_to_paid_version_for_free) {
            return false;
        }

        if (! config('monica.requires_subscription')) {
            return false;
        }

        if ($account->isSubscribed()) {
            return false;
        }

        return true;
    }

    /**
     * Indicate whether an account has reached the contact limit if the account
     * is on a free trial.
     *
     * @param  Account  $account
     * @return bool
     */
    public static function hasReachedContactLimit(Account $account): bool
    {
        return $account->allContacts()->real()->active()->count() >= config('monica.number_of_allowed_contacts_free_account');
    }

    /**
     * Indicate whether an account has not reached the contact limit of free accounts.
     *
     * @param  Account  $account
     * @return bool
     */
    public static function isBelowContactLimit(Account $account): bool
    {
        return $account->allContacts()->real()->active()->count() <= config('monica.number_of_allowed_contacts_free_account');
    }

    /**
     * Check if the account can be downgraded, based on a set of rules.
     *
     * @param  Account  $account
     * @return bool
     */
    public static function canDowngrade(Account $account): bool
    {
        $canDowngrade = true;
        $numberOfUsers = $account->users()->count();
        $numberPendingInvitations = $account->invitations()->count();
        $numberActiveContacts = $account->allContacts()->active()->count();

        // number of users in the account should be == 1
        if ($numberOfUsers > 1) {
            $canDowngrade = false;
        }

        // there should not be any pending user invitations
        if ($numberPendingInvitations > 0) {
            $canDowngrade = false;
        }

        // there should not be more than the number of contacts allowed
        if ($numberActiveContacts > config('monica.number_of_allowed_contacts_free_account')) {
            $canDowngrade = false;
        }

        return $canDowngrade;
    }

    /**
     * Get the default gender for this account.
     *
     * @param  Account  $account
     * @return string
     */
    public static function getDefaultGender(Account $account): string
    {
        $defaultGenderType = Gender::UNKNOWN;

        if ($account->default_gender_id) {
            $defaultGender = Gender::where([
                'account_id' => $account->id,
            ])->find($account->default_gender_id);

            if ($defaultGender) {
                $defaultGenderType = $defaultGender->type;
            }
        }

        return $defaultGenderType;
    }

    /**
     * Get the reminders for the month given in parameter.
     * - 0 means current month
     * - 1 means month+1
     * - 2 means month+2...
     *
     * @param  Account  $account
     * @param  int  $month
     */
    public static function getUpcomingRemindersForMonth(Account $account, int $month)
    {
        $startOfMonth = now(DateHelper::getTimezone())->addMonthsNoOverflow($month)->startOfMonth();

        // don't get reminders for past events:
        if ($startOfMonth->isPast()) {
            $startOfMonth = now(DateHelper::getTimezone());
        }

        $endOfMonth = now(DateHelper::getTimezone())->addMonthsNoOverflow($month)->endOfMonth();

        return $account->reminderOutboxes()
            ->with(['reminder', 'reminder.contact'])
            ->whereBetween('planned_date', [$startOfMonth, $endOfMonth])
            ->where([
                'user_id' => auth()->user()->id,
                'nature' => 'reminder',
            ])
            ->orderBy('planned_date', 'asc')
            ->get()
            ->filter(function ($reminderOutbox) {
                return $reminderOutbox->reminder->contact !== null;
            });
    }

    /**
     * Get the number of activities grouped by year.
     *
     * @param  Account  $account
     * @return Collection
     */
    public static function getYearlyActivitiesStatistics(Account $account): Collection
    {
        $activitiesStatistics = collect([]);
        $activities = $account->activities()
            ->select('happened_at')
            ->latest('happened_at')
            ->get();
        $years = [];

        foreach ($activities as $activity) {
            $yearStatistic = $activity->happened_at->format('Y');
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
     * Get the number of calls grouped by year.
     *
     * @return Collection
     */
    public static function getYearlyCallStatistics(Account $account): Collection
    {
        $callsStatistics = collect([]);
        $calls = $account->calls()
            ->select('called_at')
            ->latest('called_at')
            ->get();
        $years = [];

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
}
