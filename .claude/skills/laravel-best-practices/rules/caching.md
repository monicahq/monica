# Caching best practices

## Use `Cache::remember()` for cache-aside reads

`Cache::remember()` implements a cache-aside read without a separate truthiness check. It does not prevent concurrent requests from computing the same missing value. When you must prevent duplicate computation, use an atomic lock.

The manual version below treats valid falsy values, such as `false` or `0`, as cache misses.

Incorrect:

```php
$val = Cache::get('stats');
if (! $val) {
    $val = $this->computeStats();
    Cache::put('stats', $val, 60);
}
```

Correct:

```php
$val = Cache::remember('stats', 60, fn () => $this->computeStats());
```

## Consider `Cache::flexible()` for stale-while-revalidate

For frequently read keys, `Cache::flexible()` can serve stale data during a defined stale period and register a deferred refresh. During an HTTP request, that refresh normally runs after the response. The refresh is not a durable background job. After the stale period ends, the request recomputes the value synchronously.

Synchronous expiration:

```php
Cache::remember('users', 300, fn () => User::all());
```

Stale-while-revalidate tradeoff:

```php
Cache::flexible('users', [300, 600], fn () => User::all());
```

In this example, the value is fresh for five minutes. Laravel may serve the stale value until ten minutes after it was cached.

## Use `Cache::memo()` to avoid redundant hits within an execution

When code reads the same cache key repeatedly during one request or job, `memo()` decorates a cache store and keeps resolved values in memory for that execution.

```php
$settings = Cache::memo()->get('settings');
```

Repeated reads through the same memoized store avoid additional store lookups. Writes through the memoized store update or invalidate its in-memory values as appropriate.

## Use cache tags to invalidate related groups

Tags group related entries for invalidation without tracking each key. The `file`, `dynamodb`, and `database` drivers do not support cache tags. Confirm that a store supports tags before you choose it.

```php
Cache::tags(['user-1'])->flush();
```

## Use `Cache::add()` for atomic conditional writes

`add()` atomically writes a value only when the key does not already exist.

Incorrect:

```php
if (! Cache::has('lock')) {
    Cache::put('lock', true, 10);
}
```

Correct:

```php
Cache::add('lock', true, 10);
```

When you need lock ownership and safe release, use `Cache::lock()` instead of an ordinary cache key.

## Use `once()` for in-process memoization

`once()` memoizes a callback's return value for the current request or job. Calls made from an object instance are scoped to that instance. Unlike `Cache::memo()`, `once()` does not read from an external cache store.

```php
public function roles(): Collection
{
    return once(fn () => $this->loadRoles());
}
```

Repeated calls return the memoized result without rerunning the callback. Use `once()` for repeated computation within one execution. Use `Cache::memo()` to memoize access to an underlying store that can also persist values across executions.

## Configure failover cache stores in production

The failover driver tries each configured store in order when a store operation throws an exception. It does not consult later stores for an ordinary cache miss, and it does not replicate data between stores.

```php
'failover' => ['driver' => 'failover', 'stores' => ['redis', 'database']],
```
