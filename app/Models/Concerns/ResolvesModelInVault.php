<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Override;

/**
 * Route model binding for a record that lives inside a vault.
 *
 * Neither half of the address can be trusted on its own. The account has to
 * match, or one account reads another's records; and the record has to be in the
 * vault the URL actually names, or the address of one vault opens somebody kept
 * in another. Both are refused as not found rather than forbidden, since saying
 * "you may not" would already say that the record exists.
 *
 * The vault is read back from the route rather than passed in, because binding
 * is called by the router with nothing but a value. Laravel substitutes the
 * parameters in the order the URI names them, and every route reaching one of
 * these records spells the vault first, so it is already a model by the time
 * this runs.
 *
 * The address is the route key, and the primary key is accepted as a second
 * spelling of it. That second attempt is scoped exactly like the first.
 *
 * Use it on a model kept in a vault. A model the account holds directly takes
 * {@see ResolvesModelInAccount} instead.
 */
trait ResolvesModelInVault
{
    /**
     * Find the record a URL points at, within the account of whoever is asking
     * and the vault the URL names.
     *
     * @param  string  $value
     * @param  string|null  $field
     */
    #[Override]
    public function resolveRouteBinding($value, $field = null): ?self
    {
        $columns = $field !== null ? [$field] : $this->routeKeyColumns();

        foreach ($columns as $column) {
            $record = $this->findInVault($column, $value);

            if ($record !== null) {
                return $record;
            }
        }

        if (in_array('id', $columns, true) || ! ctype_digit((string) $value)) {
            return null;
        }

        return $this->findInVault('id', $value);
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
     * Find one record by the column named, inside the account and, when the URL
     * names one, inside the vault.
     *
     * A route that names no vault is still answered, and still only ever with a
     * record of the account. The account clause is what makes that safe: without
     * it the query would reach every vault of every account, which is the one
     * answer this trait exists to prevent.
     *
     * The id is only ever asked for with a value that reads as one, because a
     * database is entitled to refuse a uuid compared against an integer column.
     */
    private function findInVault(string $field, mixed $value): ?self
    {
        $accountId = Auth::user()?->account_id;

        if ($accountId === null) {
            return null;
        }

        $query = $this->query()
            ->where($field, $value)
            ->where('account_id', $accountId);

        $vault = $this->vaultNamedByTheUrl();

        if ($vault instanceof Vault) {
            $query->where('vault_id', $vault->id);
        }

        return $query->first();
    }

    /**
     * The vault the current route names, or null when it names none.
     *
     * A parameter that is present but is not a vault of this account is refused
     * outright instead of being ignored. Ignoring it would drop the lookup to
     * the account alone, which is how a record of another vault would be reached
     * through an address that named a vault it is not in.
     */
    private function vaultNamedByTheUrl(): ?Vault
    {
        $vault = Route::current()?->parameter('vault');

        if ($vault === null) {
            return null;
        }

        if (! $vault instanceof Vault || $vault->account_id !== Auth::user()?->account_id) {
            throw new ModelNotFoundException;
        }

        return $vault;
    }
}
