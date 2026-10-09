# Research: Configurable dispatch queues

Framework read: `laravel/framework` v13.34.0, the version `composer.lock` pins.

## R1. Forward the priority names instead of editing every dispatch

- **Decision**: register `Queue::forward(['high' => …, 'default' => …, 'low' => …])` once, at boot. The 210 `onQueue('high')` and `onQueue('low')` calls, the five `Schedule::job(…, 'low' | 'high')` calls and the 20 notifications stay exactly as they are: `high` and `low` become priority names, and the forward turns each into the configured queue name.
- **Rationale**: `QueueRoutes::forwardedQueue()` is applied inside each driver's `getQueue()` (`DatabaseQueue:676`, `RedisQueue:674` and `:685`, `SqsQueue:689`, `BeanstalkdQueue:370`). `getQueue()` runs on push and on pop alike, so one registration covers every dispatch site, the scheduler and the notifications (FR-003, FR-004) and the worker side too (R3). It is the documented framework mechanism (principle IV) and one line of code (principle II).
- **Alternatives considered**: replace the literal at 210 sites with `config(…)` (a large diff, and every future dispatch must remember it); a `QueuePriorityEnum` with a method returning the configured name (the same 210 sites, plus a new type); `Queue::route()` per job class (routes by class, which is the per kind choice the clarification refused).

## R2. Where the names live and how a blank one falls back

- **Decision**: `config/monica.php` gains `'queues' => ['high' => env('MONICA_QUEUE_HIGH', 'high'), 'low' => env('MONICA_QUEUE_LOW', 'low')]`, documented in the file with three comment lines and in `.env.example`. The provider forwards only the names that are `filled()`.
- **Rationale**: the config skill puts every Monica value in `config/monica.php` under a `MONICA_` variable. A variable written as `MONICA_QUEUE_HIGH=` reaches `env()` as an empty string, not as the default, so the fallback has to happen after reading. Forwarding nothing for a blank name leaves `high` on `high`, which is the shipped name (FR-006), and keeps that default in one place.
- **Alternatives considered**: `trim(env(…)) ?: 'high'` in the config file (the fallback cannot be reached from a test without rewriting the environment, and no test in the suite does that); an instance setting (refused in clarification).

## R3. Shipped workers need no change

- **Decision**: leave `--queue=high,default,low` in `Dockerfile:206`, `compose.yaml:51`, `docker-compose.yml:33` and `composer.json:102` as it is.
- **Rationale**: `Worker::getNextJob()` calls `$connection->pop($queue)` for each name of the list, and `pop()` goes through `getQueue()`, so a worker booted with the forward polls the configured queues in the same order (FR-008). The worker list names priorities, exactly like the dispatch sites. When two names resolve to the same queue, the worker asks that queue twice in one round, which costs one empty read and changes no order.
- **Alternatives considered**: interpolate shell variables into each command (the shell does not see `.env`, so the container and `composer dev` would drift from what the application reads); a wrapper Artisan command that builds `--queue` (a new command to replace one that already works).

## R4. Draining and retrying across a rename

- **Decision**: document, in `README.md` next to the description of the `queue` service, that a name is changed while its queue is empty and is followed by `php artisan queue:restart`.
- **Rationale**: once `high` is forwarded, nothing addresses the literal `high` queue any more, so work left there cannot be drained by a worker of this instance. A failed job records the resolved name it failed on, and `queue:retry` pushes it back to that name: unchanged settings send it back where it came from, and a failure recorded before a rename returns to the old name (spec, Edge Cases).
- **Alternatives considered**: forward on push only (the framework offers no such switch).

## R5. How the forward is proven

- **Decision**: two tests in `tests/Unit/Providers/AppServiceProviderTest.php`, on the `database` queue connection: with `monica.queues.high` set to `monica-high`, a job sent on `high` is stored on `monica-high` and is popped by asking for `high`; with a blank name, it is stored on `high`. The provider is booted again after the configuration is set.
- **Rationale**: the suite runs on `sync`, and `Queue::fake()` records the name it was handed rather than the resolved one, so only a real driver shows the forward. Existing `assertPushedOn('low', …)` assertions keep passing for that same reason, and they now assert the priority, which is what they mean.
- **Alternatives considered**: one test per driver (the forward is the framework's, applied identically in each driver; this project tests that it registers it).

## Found on the way

`config/scout.php:78` said search syncing runs on the `high` queue while the value two lines below was `'default'`. Search stays on `default`, and the comment now says so.
