---

description: "Task list for Resend delivery webhooks"
---

# Tasks: Resend delivery webhooks

**Input**: Design documents from `/specs/015-resend-webhooks/`

**Tests**: Included, and not optional here. Principle III is non-negotiable: every action, model, listener, view model and enum ships with a test.

**Length**: Trimmed to one line per task, phase headings and the dependency list, per Principle XI. The template's parallel examples, implementation strategy and notes sections are deliberately absent.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

- [X] T001 Add `RESEND_WEBHOOK_SECRET=` to `.env.example`, under the existing Resend comment beside `RESEND_API_KEY`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: the column, the fourth state and the test fixture every story below needs.

- [X] T002 Add `complained_at` datetime nullable after `bounced_at`, and `$table->index('uuid')`, to `database/migrations/2026_07_31_000000_create_emails_sent_table.php`, commenting the column per the migrations skill (existing local databases need `php artisan migrate:fresh`)
- [X] T003 [P] Add `Complained = 'complained'` to `app/Enums/EmailDeliveryStatusEnum.php`
- [X] T004 [P] Add a `complained()` state to `database/factories/EmailSentFactory.php`, beside `delivered()` and `bounced()`
- [X] T005 [P] Create `tests/Fixtures/Resend/SignedWebhookRequest.php`, building the `svix-id`, `svix-timestamp` and `svix-signature` headers for a payload and secret
- [X] T006 Add `complained_at` to the `#[Fillable]` list, the `casts()` array and the class docblock of `app/Models/EmailSent.php`, and add the complaint arm to `deliveryStatus()` above the bounce arm
- [X] T007 [P] Cover the new case in `tests/Unit/Enums/EmailDeliveryStatusEnumTest.php`
- [X] T008 Cover the complaint arm in `tests/Unit/Models/EmailSentTest.php`, including its precedence over a bounce and over a delivery

**Checkpoint**: the column exists and reads as a fourth state. Nothing writes it yet.

---

## Phase 3: User Story 1 - Knowing whether an email arrived (Priority: P1) 🎯 MVP

**Goal**: four provider reports reach the journal, and the screen and the API stop saying "On its way" for an email that has arrived, bounced or been marked as spam.

**Independent Test**: post a signed `email.delivered` at the package's path for a known identifier, then read the outcome off the emails sent screen and off the API.

- [X] T009 [US1] Create `app/Actions/RecordEmailDeliveryOutcome.php`, taking the payload array and an `EmailDeliveryStatusEnum`, reading `data.email_id` and the top level `created_at` (never `data.created_at`), finding the row by `uuid` and writing the one timestamp only where it is currently null
- [X] T010 [P] [US1] Create `app/Listeners/RecordEmailDelivered.php`, delegating to the action with `Delivered`
- [X] T011 [P] [US1] Create `app/Listeners/RecordEmailBounced.php`, delegating with `Bounced`
- [X] T012 [P] [US1] Create `app/Listeners/RecordEmailFailed.php`, delegating with `Bounced`, since a failed send also reached nobody
- [X] T013 [P] [US1] Create `app/Listeners/RecordEmailComplained.php`, delegating with `Complained`
- [X] T014 [US1] Delete `app/Listeners/EmailDelivered.php`, replaced by T010
- [X] T015 [P] [US1] Add the fourth `match` arm to `resources/views/app/settings/account/logs/_email-sent-row.blade.php`, tone `danger`, with a translated word for the complaint
- [X] T016 [P] [US1] Add `complained_at` to `app/Http/Resources/EmailSentResource.php`, beside the other three timestamps
- [X] T017 [P] [US1] Add the `complained_at` field, and the fourth `delivery_status` word, to `resources/docs/api/07-emails-sent.php`
- [X] T018 [US1] Run `php artisan monica:localize` and translate the new word in the eight `lang/*.json` files beside `en`, following `.claude/rules/translations.md`
- [X] T019 [US1] Cover the action in `tests/Unit/Actions/RecordEmailDeliveryOutcomeTest.php`: each outcome writes its own column, the instant comes from the top level `created_at`, a repeated report changes nothing, and a bounce with a delivery in either order reads as bounced
- [X] T020 [P] [US1] Cover the four listeners in `tests/Unit/Listeners/RecordEmailDeliveredTest.php`, `RecordEmailBouncedTest.php`, `RecordEmailFailedTest.php` and `RecordEmailComplainedTest.php`, one file each
- [X] T021 [US1] Cover the whole chain in `tests/Feature/Webhooks/ResendWebhookTest.php`: a signed `email.delivered` posted at `resend/webhook` sets `delivered_at`, which proves route, middleware, discovery, listener and action are connected
- [X] T022 [P] [US1] Cover `complained_at` and the `complained` status in `tests/Feature/Controllers/Api/EmailSentControllerTest.php`
- [X] T023 [P] [US1] Cover the fourth badge in `tests/Feature/Controllers/App/Settings/Account/Logs/EmailSentControllerTest.php`
- [X] T024 [US1] Cover the accepted temporary bounce consequence in `tests/Unit/Actions/RecordEmailDeliveryOutcomeTest.php`: a bounce whose `data.bounce.type` is `Temporary` is recorded like any other, and a later delivery does not overturn it

**Checkpoint**: outcomes reach the journal, the screen and the API. Not yet shippable: see US2.

---

## Phase 4: User Story 2 - Nothing unproven reaches the journal (Priority: P2)

**Goal**: an instance with no shared secret acts on nothing, which is every instance sending mail some other way.

**Independent Test**: post a well formed body with no signature, with a wrong signature, and with no secret configured, and confirm the journal is untouched in all three cases.

- [X] T025 [US2] Guard `app/Actions/RecordEmailDeliveryOutcome.php`: act on nothing unless `config('resend.webhook.secret')` is a non-empty string, logging one warning when it declines
- [X] T026 [P] [US2] Cover the guard in `tests/Unit/Actions/RecordEmailDeliveryOutcomeTest.php`
- [X] T027 [US2] Cover both refusals in `tests/Feature/Webhooks/ResendWebhookTest.php`: with a secret set, a wrong signature is refused by the route and changes nothing; with no secret, a well formed body changes nothing whatever the route answers
- [X] T028 [US2] Assert in `tests/Feature/Webhooks/ResendWebhookTest.php` that `resend/webhook` still carries `Resend\Laravel\Http\Middleware\VerifyWebhookSignature` when a secret is configured, so a package upgrade cannot quietly drop the check

**Checkpoint**: US1 and US2 together are shippable.

---

## Phase 5: User Story 3 - Everything else the provider says is heard and dropped (Priority: P3)

**Goal**: no report can turn the package's 200 into a 500, and no open or click is ever stored.

**Independent Test**: post one signed report of every other kind the provider sends, and one naming an identifier nobody holds, and confirm each is answered without a row changing.

- [X] T029 [US3] Make `app/Actions/RecordEmailDeliveryOutcome.php` return rather than throw when the payload carries no identifier, when the instant is unparseable, or when no row holds that identifier, logging one warning in each case
- [X] T030 [P] [US3] Cover those declines in `tests/Unit/Actions/RecordEmailDeliveryOutcomeTest.php`
- [X] T031 [US3] Cover in `tests/Feature/Webhooks/ResendWebhookTest.php` that a signed `email.opened` and a signed report for an unknown identifier each leave every row untouched and never answer 500

**Checkpoint**: all three stories independently functional.

---

## Phase 6: Polish

- [X] T032 Update `spec/models/005-email-sent-in-progress.md` to record that `complained_at` now exists, and that `bounced_at` has two sources
- [X] T033 Run `vendor/bin/pint --dirty --format agent`, PHPStan over `app/`, `routes/` and `database/`, and `php artisan monica:localize --check`
- [X] T034 Walk `specs/015-resend-webhooks/quickstart.md` end to end, including its last row, the case the route cannot protect

---

## Dependencies & Execution Order

- **Phase 1** has no dependencies.
- **Phase 2** blocks every story. T006 needs T002 and T003; T008 needs T006; T003, T004, T005 and T007 are parallel.
- **Phase 3** needs Phase 2. T010 to T013 need T009 and are parallel with each other. T014 needs T010. T018 needs T015. T019, T020, T021 and T024 need their subjects. T015, T016, T017, T022 and T023 touch separate files.
- **Phase 4** needs T009, since it adds a guard to that action. It is a release gate for Phase 3 rather than a later increment: the spec says so, and shipping Phase 3 alone would leave an unsecured instance writing to journals.
- **Phase 5** needs T009, and touches the same action again. Independent of Phase 4.
- **Phase 6** needs every story that is being shipped.
- Phases 4 and 5 both edit `app/Actions/RecordEmailDeliveryOutcome.php`, so they are not parallel with each other despite being independent in behaviour.
