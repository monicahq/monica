# Tasks: Configurable dispatch queues

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`. The forward is one registration, so its rule is tested once, in `tests/Unit/Providers/AppServiceProviderTest.php`, on the real `database` queue driver (research R5). No dispatch site gets a test of its own.

## Phase 1: Setup

- [X] T000 Work on branch `2026-10-05-configurable-dispatch-queues`, cut from `main` at `504428a1` (constitution, Development Workflow)
- [X] T001 Read `.claude/skills/{config,tests,feature-documentator}/SKILL.md` and `.claude/rules/{code-style,testing}.md`

## Phase 2: Foundational

- [X] T002 [P] Add to `config/monica.php`, before `'screenshot_link'`, a `'queues' => ['high' => env('MONICA_QUEUE_HIGH', 'high'), 'low' => env('MONICA_QUEUE_LOW', 'low')]` entry under a block comment in the file's style saying the two values name the queue behind each priority, that a blank value keeps the shipped name, and that a worker reads them only when it starts
- [X] T003 [P] Add `MONICA_QUEUE_HIGH=high` and `MONICA_QUEUE_LOW=low` to `.env.example`, after `MONICA_SCREENSHOT_LINK_LIFETIME_IN_MINUTES`, under a `#` comment of two or three lines in the file's style saying what they name and that workers must be restarted after a change

## Phase 3: User Story 1, name the queues the instance sends to (P1) MVP

**Independent test**: with `monica.queues.high` set to `monica-high`, work sent on `high` is stored on `monica-high`, and a worker asking for `high` receives it.

- [X] T004 [US1] Test in `tests/Unit/Providers/AppServiceProviderTest.php` (already `RefreshDatabase`): `it_forwards_the_high_priority_to_the_configured_queue` sets `monica.queues.high` to `monica-high`, reboots with `$this->app->getProvider(AppServiceProvider::class)->boot()`, calls `Queue::connection('database')->pushRaw('{}', 'high')`, asserts `assertDatabaseHas('jobs', ['queue' => 'monica-high'])`, and asserts `Queue::connection('database')->pop('high')?->getQueue()` is `monica-high`; `it_keeps_the_shipped_name_when_the_setting_is_blank` does the same with `'  '` and asserts the row is on `high`
- [X] T005 [US1] Add `private function configureQueues(): void` to `app/Providers/AppServiceProvider.php`, calling `Queue::forward(array_filter(config('monica.queues'), filled(...)))`, with a docblock of two or three lines saying `high` and `low` name priorities and the forward resolves them on push and on poll alike, and call it from `boot()` after `configureSearch()`
- [X] T006 [US1] Run `php artisan test --compact tests/Unit/Providers/AppServiceProviderTest.php`, then the whole suite, and confirm no `assertPushedOn('high' | 'low', …)` needed a change (research R5)

## Phase 4: User Story 2, run one worker for everything (P2)

**Independent test**: with both priorities set to `default`, work sent on `high` and on `low` is received by a worker asking for `default` alone.

- [X] T007 [US2] Add `it_forwards_both_priorities_to_the_default_queue` to `tests/Unit/Providers/AppServiceProviderTest.php`: both names set to `default`, reboot, one raw push on `high` and one on `low`, two `jobs` rows on `default`, and two successful `pop('default')` calls

## Phase 5: User Story 3, shipped workers follow the setting (P3)

**Independent test**: quickstart.md step 2, the shipped `queue` service empties `monica-high` and `monica-low` with its command untouched.

- [X] T008 [US3] Confirm, without editing them, that `Dockerfile:206`, `compose.yaml:51`, `docker-compose.yml:33` and `composer.json:102` still pass `--queue=high,default,low` (research R3), and that `grep -rhoE "onQueue\('[^']*'\)|Schedule::job\([^;]*" app routes` names no queue other than `high` and `low` (210 on 2026-10-05), so every place that sends work reaches a forward (SC-002); report any other name rather than adding a forward for it

## Phase 6: Polish

- [X] T009 [P] Add to `README.md`, after the paragraph describing the `queue` service, a short paragraph naming `MONICA_QUEUE_HIGH` and `MONICA_QUEUE_LOW`, their defaults, that the worker command keeps naming `high,default,low`, and that a name is changed while its queue is empty and followed by `php artisan queue:restart` (FR-011, research R4); no dash as punctuation
- [X] T010 [P] Append to `docs/FEATURES.md` the line `* Hosting: Let whoever runs the instance name the queues behind the high and low priorities.`
- [X] T011 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress` and `php artisan monica:localize --check`; fix what they report
- [X] T013 Add the default priority: `'default' => env('MONICA_QUEUE_DEFAULT', 'default')` in `config/monica.php`, a test that work naming no queue reaches the configured default name, and `config/scout.php` kept on `default` with its comment saying so; `.env.example` untouched
- [ ] T012 Walk quickstart.md "By hand" steps 1 to 5 on the shipped stack, or report each step that could not be run and why

## Dependencies

- T001 before everything. T002 before T004, T005 and T007. T003 independent of code.
- T004 before T005 (the test fails first), T005 before T006 and T007.
- T008 needs T005 only to be meaningful when run by hand (T012).
- T009 and T010 in parallel once T005 is done; T011 after every code task; T012 last.

## Parallel opportunities

- T002 and T003 touch different files.
- T009 and T010 touch different files, and neither touches code.

## Implementation strategy

MVP is Phase 3: after T006 the instance honours both settings everywhere it sends and polls. Phases 4 and 5 add one test and one check, Phase 6 the documentation and the gates.
