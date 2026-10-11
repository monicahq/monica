<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;
use Override;

/**
 * Route model binding for a record the account holds directly.
 *
 * A record of another account is not found rather than forbidden: the account is
 * a compartment, and answering "you may not" would already say that the record
 * exists. Every query written here carries `account_id` for that reason, and a
 * miss comes back as null so the router answers 404.
 *
 * The address is the route key, and the primary key is accepted as a second
 * spelling of it, so a link built from an id still reaches its record. That
 * second attempt is scoped exactly like the first.
 *
 * Use it on a model whose place in the application is the account itself. A
 * model that lives inside a vault takes {@see ResolvesModelInVault} instead,
 * which adds the vault to the same reasoning.
 */
trait ResolvesModelInAccount
{
    /**
     * Find the record a URL points at, within the account of whoever is asking.
     *
     * @param  string  $value
     * @param  string|null  $field
     */
    #[Override]
    public function resolveRouteBinding($value, $field = null): ?self
    {
        $columns = $field !== null ? [$field] : $this->routeKeyColumns();

        foreach ($columns as $column) {
            $record = $this->findInAccount($column, $value);

            if ($record !== null) {
                return $record;
            }
        }

        if (in_array('id', $columns, true) || ! ctype_digit((string) $value)) {
            return null;
        }

        return $this->findInAccount('id', $value);
    }

    /**
     * The columns an address may name this record by, tried in order.
     *
     * One column is the ordinary case and is exactly what the router asked for before this existed.
     * A model reached by more than one spelling of its address overrides this, and every attempt is
     * scoped the same way, so widening what is accepted never widens what is reachable.
     *
     * @return list<string>
     */
    protected function routeKeyColumns(): array
    {
        return [$this->getRouteKeyName()];
    }

    /**
     * Whether a record found in the account may be reached through an address
     * at all. A model withdrawing some of its records from every route, while
     * keeping them, says so here.
     */
    protected function isReachableThroughAnAddress(): bool
    {
        return true;
    }

    /**
     * Find one record of the account by the column named.
     *
     * Nobody signed in means no account to look in, and a query with no account
     * is one nobody is entitled to, so it is refused rather than widened. That
     * cannot happen down a route of the application, where binding runs behind
     * the `auth` middleware, and it is the answer if a route is ever written
     * without it.
     *
     * The id is only ever asked for with a value that reads as one, because a
     * database is entitled to refuse a uuid compared against an integer column.
     */
    private function findInAccount(string $field, mixed $value): ?self
    {
        $accountId = Auth::user()?->account_id;

        if ($accountId === null) {
            return null;
        }

        $record = $this->query()
            ->where($field, $value)
            ->where('account_id', $accountId)
            ->first();

        if ($record === null || ! $record->isReachableThroughAnAddress()) {
            return null;
        }

        return $record;
    }
}
