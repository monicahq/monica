# Task scheduling best practices

## Prevent unwanted overlap

Use `withoutOverlapping()` when a second run must not begin while the previous run holds the lock. Use it for variable-duration tasks that are not safe to run concurrently.

```php
Schedule::command('reports:generate')
    ->everyFifteenMinutes()
    ->withoutOverlapping(30);
```

The optional value is the lock expiration time in minutes, not the task timeout. Choose the value carefully. The default is 24 hours, and an expiration that is too short can permit overlap while the first task still runs. Clear stale locks with `php artisan schedule:clear-cache`. The task itself should still tolerate retries and partial execution where practical.

## Run a task on one server

Use `onOneServer()` when only one scheduler node should run an eligible task. Scheduler nodes must use the same default cache store, and that store must support atomic locks. Supported stores include `database`, `memcached`, `dynamodb`, and `redis`.

```php
Schedule::command('billing:charge')->daily()->onOneServer();
```

Name scheduled closures before applying `onOneServer()`, especially when scheduling the same closure with different parameters, so each task has a distinct lock identity.

## Run eligible commands in the background

Tasks due at the same time run sequentially by default. Use `runInBackground()` when an independent, long-running scheduled command should not delay later tasks.

```php
Schedule::command('analytics:process')->hourly()->runInBackground();
```

Laravel restricts `runInBackground()` to tasks scheduled with `command()` and `exec()`. It is not available for scheduled closures. Give background processes logging and failure monitoring.

## Restrict tasks by environment

Use `environments()` when a task should run only in named application environments. Treat `environments()` as an operational safeguard, not an authorization control.

```php
Schedule::command('billing:charge')
    ->monthly()
    ->environments(['production']);
```

## Group shared configuration

Use schedule groups when several tasks share frequency or constraints.

```php
Schedule::daily()
    ->onOneServer()
    ->timezone('America/New_York')
    ->group(function () {
        Schedule::command('emails:send --force');
        Schedule::command('emails:prune');
    });
```

## Bound work inside the task

The scheduler does not provide a `takeUntilTimeout()` event method or terminate arbitrary tasks at a deadline. Bound work in the command or job itself by processing finite chunks, checking a deadline, or dispatching queue jobs with suitable timeouts. Use operating-system or process controls when hard termination is required.
