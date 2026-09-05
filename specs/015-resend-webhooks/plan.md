# Implementation Plan: Resend delivery webhooks

**Branch**: `20260904-resend-webhook` | **Date**: 2026-09-04 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/015-resend-webhooks/spec.md`

## Summary

Consume the endpoint the provider's integration package already ships. Four of its events get a listener each, three lines of body apiece, and all four delegate to one action that guards on the secret, reads the payload, finds the row and writes one timestamp. Nothing is routed, no middleware is written, no controller exists. What makes an unconfigured instance safe is the guard inside the action, not a route we own, which is the whole difference from the previous draft. Decisions and rejected alternatives are in [research.md](research.md); the column, the event mapping and the payload we read are in [data-model.md](data-model.md). `contracts/` is deliberately absent: the receiving interface belongs to the package, so the only contract this feature owns is which events it consumes and which fields it reads, and `data-model.md` carries that. Principle XI requires the omission rather than a second copy.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 13.29, `resend/resend-laravel` v1.4.0 (the route, its signature middleware, its event classes, and the transport that stamps the identifier), `resend/resend-php` v1.12.0 (the signature algorithm, used by the package and by our test fixture)

**Storage**: the existing `emails_sent` table. One new nullable timestamp, one new index

**Testing**: PHPUnit, `RefreshDatabase`. One feature test posts a signed body at the package's path, which is the only test that proves route, middleware, discovery, listener and action are actually connected

**Target Platform**: Linux web server, the same build self hosted and hosted

**Project Type**: server rendered Laravel application with a JSON API beside it

**Performance Goals**: one indexed lookup and one update per report, in the request, with no queue and no scheduled work

**Constraints**: a listener must never throw, because the package answers 200 and a thrown exception turns that into a 500 the provider retries. The suite passes on SQLite, MySQL and PostgreSQL. PHPStan level 5. No `env()` outside `config/`

**Scale/Scope**: at most three timestamps per email sent, one row each, no fan out

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | How this feature satisfies it |
| --- | --- |
| I Actions hold the logic | One action, `RecordEmailDeliveryOutcome`. The four listeners name an outcome and delegate; none of them holds a rule |
| II Simplicity over abstraction | Four listeners, one action, one column, one index, one enum case. No route, no middleware, no controller, no queue, no event log, and no second enum: the event to fact mapping is the listener class names |
| III Tested before merged | One feature test through the package's path, four thin listener tests, one thorough action test, plus the accessor, the enum, the factory state, the badge and the API field. The accepted temporary bounce behaviour gets its own test so it reads as a decision |
| IV Laravel conventions | Listeners auto-discovered, named as `RecordEmailSent` is. Migration altered in place per the migrations skill, only `up()`. One deviation, named below |
| V Localized and safe | One new word on a screen, produced by `php artisan monica:localize` and translated in the eight locales beside `en` |
| VI Nothing changes under anybody's feet | Only fills in facts nobody had. A timestamp is written once and never overwritten |
| VII Stable core | Touches no core concept and adds no second way to attach data |
| VIII Nothing disappears silently | Nothing is deactivated or deleted. Suppression, which does deactivate an address, is out of scope because it owes the user an explanation and a way back |
| IX Accessible to everybody | The fourth badge state reuses `x-badge`, which carries a word and not only a tint. Binding here although the spec states it nowhere: the requirement saying so was cut at clarification as a restatement of this principle, which holds regardless |
| X Everything goes through the API | The new fact reaches the API in the same release, through `EmailSentResource` and the reference under `resources/docs/api` |
| XI The written record is short | `plan.md` 100 lines, `research.md` 80, `data-model.md` 60, `contracts/` omitted as required, `quickstart.md` written because signing a body by hand is the only way to exercise this locally |

Deliberate deviation, and the only one: the action takes the provider's payload array rather than the parsed values, unlike `RecordEmailSent`, which parses in the listener and hands `CreateEmailSent` clean scalars. There is one listener there and four here, so parsing in the listener would state four times over which key holds the instant, and that is exactly the fact the work in progress got wrong. One place, one chance to be wrong. See [research.md](research.md) decision 4.

**Post design re-check**: unchanged, and lighter than the previous draft. Phase 1 removed a route, a middleware, a controller and an enum from the design, added no folder and no dependency, and took the deviation count from one to one.

## Project Structure

### Documentation (this feature)

```text
specs/015-resend-webhooks/
├── plan.md              # This file
├── research.md          # Phase 0: the decisions
├── data-model.md        # Phase 1: the column, the mapping, the precedence
├── quickstart.md        # Phase 1: how to exercise it locally
└── tasks.md             # Phase 2 output (/speckit-tasks, NOT created here)
```

### Source Code (repository root)

```text
app/
├── Actions/RecordEmailDeliveryOutcome.php      # new: guard on the secret, read, find, write one timestamp
├── Enums/EmailDeliveryStatusEnum.php           # gains Complained
├── Http/Resources/EmailSentResource.php        # complained_at
├── Listeners/RecordEmailDelivered.php          # new, replacing EmailDelivered.php
├── Listeners/RecordEmailBounced.php            # new
├── Listeners/RecordEmailFailed.php             # new, writes the bounce timestamp
├── Listeners/RecordEmailComplained.php         # new
└── Models/EmailSent.php                        # accessor gains the complaint arm

database/
├── factories/EmailSentFactory.php              # gains a complained() state
└── migrations/2026_07_31_000000_create_emails_sent_table.php   # altered: complained_at, index on uuid

resources/
├── docs/api/07-emails-sent.php                 # complained_at, and the fourth delivery_status word
└── views/app/settings/account/logs/_email-sent-row.blade.php   # the fourth badge

tests/Fixtures/Resend/SignedWebhookRequest.php  # new: builds the svix headers. First occupant of tests/Fixtures
lang/*.json                                     # the new word in eight locales
.env.example                                    # RESEND_WEBHOOK_SECRET
```

Removed: `app/Listeners/EmailDelivered.php`. Its four faults are in [research.md](research.md) decision 8, and `RecordEmailDelivered.php` takes its place under a name that no longer collides with the event class it listens for.

**Structure Decision**: nothing new outside existing folders. `app/Listeners` gains four files and loses one, `tests/Fixtures` gets its first occupant, and no route file, controller namespace or middleware is touched. `tests/Feature/Webhooks/` is a new test folder, consistent with `tests/Feature/Localization/`, which already holds feature tests that are not controller tests.

## Complexity Tracking

No constitution violation to justify. The single convention deviation, the payload passed into the action, is stated in the Constitution Check above with its reason.
