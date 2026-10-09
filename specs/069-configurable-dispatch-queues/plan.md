# Implementation Plan: Configurable dispatch queues

**Branch**: `2026-10-05-configurable-dispatch-queues` | **Date**: 2026-10-05 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/069-configurable-dispatch-queues/spec.md`

## Summary

Three environment variables name the queue behind each priority. At boot the application forwards the priority names `high`, `default` and `low` to those queue names through the framework's queue forwarding, which every driver applies when it sends and when it polls. No dispatch site, scheduled job, notification or shipped worker command changes ([research.md](research.md) R1, R3).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13.34

**Primary Dependencies**: the framework only (`Queue::forward()`, `QueueRoutes`)

**Storage**: none; the `jobs` table is used by the tests only

**Testing**: PHPUnit, on the `database` queue connection for the two forwarding tests

**Target Platform**: the same Linux server and container image as today

**Project Type**: web application (Laravel monolith)

**Performance Goals**: none added; a forward is one array lookup per push and per poll

**Constraints**: the suite passes on SQLite, MySQL and PostgreSQL; `env()` only inside `config/`

**Scale/Scope**: about 210 dispatch sites reached by one registration; 6 files changed

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle or constraint | Verdict |
| --- | --- |
| I. Actions hold the business logic | Pass. No user operation is added; the forward is framework configuration in a provider, beside the search and billing setup already there. |
| II. Simplicity over abstraction | Pass. One private provider method of three lines; no enum, helper or command. |
| III. Tested before merged | Pass. Two tests prove the forward on a real driver (R5). |
| IV. Laravel conventions | Pass. `Queue::forward()` is the framework mechanism; read in the v13.34 source, since `search-docs` was unavailable. |
| V. Localized and safe | Pass. No string reaches a reader, no screen changes. |
| VI to IX | Not touched: no record, structure, deactivation or screen. |
| X. Everything through the API | Pass, with a reason: server configuration is no account capability (spec, Assumptions). |
| XI. The written record is short | Pass. spec 112, plan under 100, research under 80; `data-model.md` and `contracts/` omitted. |
| Product invariant: same product hosted and self hosted | Pass. Both read the same two variables. |
| Layout of the repository | Pass. No new folder. |
| Three databases | Pass. The forwarding tests run on each through the `database` queue driver. |

Re-checked after design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/069-configurable-dispatch-queues/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── checklists/requirements.md
```

`data-model.md` is omitted: the only entities are two configuration values, named in the spec. `contracts/` is omitted: the two variables below are the whole interface, and this table is their one description.

| Variable | Default | Blank or spaces | Read by |
| --- | --- | --- | --- |
| `MONICA_QUEUE_HIGH` | `high` | `high` | `config('monica.queues.high')` |
| `MONICA_QUEUE_DEFAULT` | `default` | `default` | `config('monica.queues.default')` |
| `MONICA_QUEUE_LOW` | `low` | `low` | `config('monica.queues.low')` |

### Source Code (repository root)

```text
config/monica.php                                  'queues' entry, three comment lines
config/scout.php                                   comment says search syncs on the default queue
app/Providers/AppServiceProvider.php               configureQueues(), called from boot()
tests/Unit/Providers/AppServiceProviderTest.php    the two forwarding tests (R5)
README.md                                          the settings, and changing a name while its queue is empty (R4)
docs/FEATURES.md                                   one entry, as the feature-documentator skill asks
```

**Structure Decision**: the existing monolith. `configureQueues()` forwards `array_filter(config('monica.queues'), filled(...))`, so a blank name forwards nothing and stays on its shipped name (R2).

Left unchanged on purpose: every `onQueue('high' | 'low')`, `routes/console.php`, the notifications, and the worker commands in `Dockerfile`, `compose.yaml`, `docker-compose.yml`, `composer.json` and `docs/STRIPE.md`.

## Complexity Tracking

No violation to justify.
