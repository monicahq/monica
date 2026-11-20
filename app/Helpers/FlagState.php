<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;

class FlagState
{
    /**
     * Whether a flag is on for an account. A visitor has no account, so a public page behind a
     * flag shows them what every account sees.
     */
    public static function isOn(string $flag, ?Account $account): bool
    {
        if ($account === null) {
            return Feature::for(null)->active($flag);
        }

        return $account->features()->active($flag);
    }

    /**
     * How many accounts a flag is on for. An account never asked has no stored answer and gets the
     * answer stored for nobody, so it counts with that one.
     */
    public static function accountsOn(string $flag): int
    {
        $storedAnswers = DB::table('features')
            ->where('name', $flag)
            ->whereNot('scope', Feature::serializeScope(null));

        $storedOn = (clone $storedAnswers)->where('value', 'true')->count();

        if (! Feature::for(null)->active($flag)) {
            return $storedOn;
        }

        return $storedOn + Account::query()->count() - $storedAnswers->count();
    }

    /**
     * Narrow a query of accounts to those a flag is on for, by the rule {@see accountsOn()} counts
     * with. Pennant stores an answer for every account it was asked about, so the stored scopes are
     * matched in the database rather than gathered into a list of ids.
     *
     * @param  Builder<Account>  $accounts
     * @return Builder<Account>
     */
    public static function whereOn(Builder $accounts, string $flag): Builder
    {
        $prefix = Feature::serializeScope(new Account);
        $scope = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? 'concat(?, accounts.id)'
            : 'cast(? as text) || accounts.id';

        $storedAnswer = fn (string $value) => fn (QueryBuilder $features) => $features
            ->from('features')
            ->where('name', $flag)
            ->whereRaw("scope = {$scope}", [$prefix])
            ->where('value', $value);

        if (Feature::for(null)->active($flag)) {
            return $accounts->whereNotExists($storedAnswer('false'));
        }

        return $accounts->whereExists($storedAnswer('true'));
    }
}
