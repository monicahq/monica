# Advanced query best practices

## Select single relationship values with subqueries

When you need only one value from a has-many relationship, consider a correlated subquery with `addSelect()` instead of loading the entire relationship. The subquery selects the value as part of the main query, without an additional relationship query.

```php
public function scopeWithLastLoginAt($query): void
{
    $query->addSelect([
        'last_login_at' => Login::select('created_at')
            ->whereColumn('user_id', 'users.id')
            ->latest()
            ->take(1),
    ])->withCasts(['last_login_at' => 'datetime']);
}
```

## Create dynamic relationships with a subquery foreign key

The same pattern can select a foreign key and expose the selected model through a `belongsTo` relationship. Eager loading that relationship still runs a separate query, but the pattern avoids loading the full has-many collection.

```php
public function lastLogin(): BelongsTo
{
    return $this->belongsTo(Login::class, 'last_login_id');
}

public function scopeWithLastLogin($query): void
{
    $query->addSelect([
        'last_login_id' => Login::select('id')
            ->whereColumn('user_id', 'users.id')
            ->latest()
            ->take(1),
    ])->with('lastLogin');
}
```

## Combine related counts with conditional aggregates

Use conditional aggregates to combine several counts over the same filtered data set into one query. Use `toBase()` when you need only scalar values and model hydration adds nothing. Confirm the expression syntax against the application's database engine.

```php
$statuses = Feature::toBase()
    ->selectRaw("count(case when status = 'Requested' then 1 end) as requested")
    ->selectRaw("count(case when status = 'Planned' then 1 end) as planned")
    ->selectRaw("count(case when status = 'Completed' then 1 end) as completed")
    ->first();
```

## Reuse loaded parent models with `setRelation()`

When a parent and its children are already loaded and code also accesses `$child->parent`, set the inverse relationship to the existing parent instance. Setting the inverse relationship avoids an extra lazy-loading query for each child.

```php
$feature->load('comments.user');
$feature->comments->each->setRelation('feature', $feature);
```

## Compare `whereHas()` with an `IN` subquery

`whereHas()` typically produces an `EXISTS` subquery, while `whereIn()` can express the same filter with an `IN` subquery. Either form can be faster, depending on the database engine, indexes, cardinality, and query plan. Measure both forms with representative data. Neither subquery loads its result set into PHP memory.

Option using `EXISTS`:

```php
$query->whereHas('company', fn ($q) => $q->where('name', 'like', $term));
```

Option using `IN`:

```php
$query->whereIn('company_id', Company::where('name', 'like', $term)->select('id'));
```

## Measure two simple queries against one complex query

Two targeted queries can outperform one complex correlated subquery or join when the first query is highly selective. They also add a database round trip, can transfer a large identifier list, and do not provide a single-query consistency snapshot. Decide from query plans and production-like measurements.

## Design composite indexes for the query

For common multi-column sorts, consider a composite index whose column order supports the query's filters and ordering. Database engines may combine indexes or choose an explicit sort, so matching the `ORDER BY` list alone does not guarantee that the engine uses the index. Verify the query plan.

```php
// Migration
$table->index(['last_name', 'first_name']);

// Query that this index may support
User::query()->orderBy('last_name')->orderBy('first_name')->paginate();
```

## Consider a correlated subquery for has-many ordering

When sorting by one value from a has-many relationship, a direct join can duplicate parent rows unless it first reduces the related table to one row per parent. A correlated subquery in `orderBy()` is often simpler, but its performance depends on the query plan and supporting indexes.

```php
public function scopeOrderByLastLogin($query): void
{
    $query->orderByDesc(Login::select('created_at')
        ->whereColumn('user_id', 'users.id')
        ->latest()
        ->take(1)
    );
}
```
