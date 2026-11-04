# Tasks: Banner offering full access to free accounts

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: included. Principle III makes every class tested before merge; layers follow `.claude/rules/testing.md`.

Format: `- [ ] T### [P?] [US?] task in path`. [P] means another file and no unfinished dependency.

## Phase 1: Setup

None. The project, its tooling and the welcome precedent already exist.

## Phase 2: Foundational

- [X] T001 Add `$table->datetime('full_access_offer_dismissed_at')->nullable()->comment('when the user hid the banner offering full access, null until then')` after `welcomed_at` in database/migrations/0001_01_01_000000_create_users_table.php, then run `php artisan migrate:fresh --seed`
- [X] T002 Add the `@property CarbonImmutable|null $full_access_offer_dismissed_at` line and the `'datetime'` cast beside `welcomed_at` in app/Models/User.php

**Checkpoint**: the column exists and reads as a date.

## Phase 3: User Story 1, a free account sees the offer (P1) MVP

**Independent test**: a metered account without full access opens the vault list, sees the banner, follows its link to `settings.billing.index`.

- [X] T003 [US1] Add `isOfferedFullAccess(): bool` to app/Models/User.php: false when `full_access_offer_dismissed_at` is set, then false unless `$this->account->isMetered()`, then `! $this->account->hasFullAccess()`, in that order (R1, R7)
- [X] T004 [US1] Test in tests/Unit/Models/UserTest.php that a metered free account is offered, a dismissed one is not, the same account under `config(['monica.self_hosted' => true])` is not, and one with `storage_limit_in_bytes` null is not
- [X] T005 [P] [US1] Read resources/views/components/notice.blade.php and button.blade.php, then create resources/views/components/full-access-offer.blade.php: a `<section>` named by `aria-labelledby`, one sentence saying full access lifts the limits of the free plan, a link to `route('settings.billing.index')`, no price, trial or discount (FR-003, FR-004, FR-007); first line comment per `rules/code-style.md`
- [X] T006 [US1] Add `public bool $offersFullAccess` to app/View/Components/Shell.php, set from `auth()->user()?->isOfferedFullAccess()` and forced false when `Route::is('settings.billing*', 'settings.checkout*', 'settings.subscription*', 'settings.invoices.*')` (R3)
- [X] T007 [P] [US1] Add `public bool $offersFullAccess` to app/View/Components/AccountLayout.php, set from `auth()->user()?->isOfferedFullAccess()`
- [X] T008 [US1] Render `@if($offersFullAccess) <x-full-access-offer /> @endif` at the top of `<main>`, before `{{ $slot }}`, in resources/views/layouts/shell.blade.php and resources/views/layouts/account.blade.php
- [X] T009 [P] [US1] Create tests/Feature/Views/Components/FullAccessOfferTest.php: the sentence, the Billing link and the region's accessible name render
- [X] T010 [P] [US1] Create tests/Feature/Views/Layouts/ShellTest.php: a free metered user sees the banner on a vault screen and not on `settings.billing.index` nor `settings.invoices.index`
- [X] T011 [P] [US1] Create tests/Feature/Views/Layouts/AccountLayoutTest.php: a free metered user sees the banner on `vaults.index`, a user with `withGrantedAccess()` does not

**Checkpoint**: the banner shows where FR-002 says and links to Billing.

## Phase 4: User Story 2, the reader dismisses it for good (P1)

**Independent test**: dismiss on the vault list, land on the same screen without the banner; the field is set over the API.

- [X] T012 [US2] Create app/Actions/MarkFullAccessOfferAsDismissed.php by copying app/Actions/MarkUserAsWelcomed.php: sets `full_access_offer_dismissed_at = now()` once and keeps the first moment; no `LogUserAction` (R2, R6)
- [X] T013 [P] [US2] Create tests/Unit/Actions/MarkFullAccessOfferAsDismissedTest.php, mirroring MarkUserAsWelcomedTest: it records the moment, and a second call keeps the first
- [X] T014 [US2] Create app/Http/Controllers/App/FullAccessOfferController.php with `update()` calling the action and returning `redirect()->back()` (R4)
- [X] T015 [US2] Add `Route::put('full-access-offer', [FullAccessOfferController::class, 'update'])->name('fullAccessOffer.update');` under `welcome.update` in routes/web.php
- [X] T016 [US2] Add to resources/views/components/full-access-offer.blade.php an `<x-form method="put" :action="route('fullAccessOffer.update')">` holding a button whose accessible name says it hides the offer for good; no confirmation (FR-005, FR-007)
- [X] T017 [P] [US2] Create tests/Feature/Controllers/App/FullAccessOfferControllerTest.php: the PUT redirects back to the referring URL and sets the column
- [X] T018 [US2] Create app/Http/Controllers/Api/MeFullAccessOfferController.php by copying app/Http/Controllers/Api/MeWelcomeController.php
- [X] T019 [US2] Add `Route::put('me/full-access-offer', [MeFullAccessOfferController::class, 'update'])->name('me.fullAccessOffer.update');` under `me.welcome.update` in routes/api.php
- [X] T020 [P] [US2] Add `'full_access_offer_dismissed_at' => $this->full_access_offer_dismissed_at?->timestamp,` beside `welcomed_at` in app/Http/Resources/UserResource.php
- [X] T021 [P] [US2] Create tests/Feature/Controllers/Api/MeFullAccessOfferControllerTest.php, mirroring MeWelcomeControllerTest: 200 and the field set; add the field to the `$jsonStructure` of every test asserting `UserResource`
- [X] T022 [P] [US2] Document `PUT /me/full-access-offer` and the field beside `/me/welcome` and `welcomed_at` in resources/docs/api/03-profile.php, following the api-docs-writer skill; `ApiDocsReturnFieldsTest` fails until this lands with T020

**Checkpoint**: one press hides the banner on every device, from the web and the API.

## Phase 5: User Story 3, a paying account never sees it (P2)

**Independent test**: subscription, lifetime and granted accounts see no banner.

- [X] T023 [US3] Add to tests/Unit/Models/UserTest.php: an account `withLifetimeAccess()`, one `withGrantedAccess()` and one subscribed through `$this->subscribeAccount($account)` from tests/TestCase.php are not offered; a dismissed account that loses full access stays not offered (FR-006)

**Checkpoint**: every access kind is covered by the rule from T003.

## Phase 6: Polish

- [X] T024 Run `php artisan monica:localize`, translate every reported string in every locale following `rules/translations.md` and its glossary, then `php artisan monica:localize --check`
- [X] T025 Run the accessibility-reviewer and rules-reviewer agents on the change and fix what they report
- [X] T026 Run the automated block of quickstart.md: the filtered tests, the full suite (shared layouts changed), `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`
- [X] T027 Walk the by-hand table in quickstart.md, and run a Lighthouse accessibility audit of `vaults.index` with the banner shown, which must report nothing the screen did not report before (SC-003)
- [X] T028 Record the feature in docs/FEATURES.md with the feature-documentator skill

## Dependencies

- T001 → T002 → T003; every story waits on Phase 2.
- US1: T003 → T006, T007 → T008; T005 → T008; tests T004, T009 to T011 once their target exists.
- US2: T012 → T014 → T015 → T016; T012 → T018 → T019; T016 edits the file T005 creates; T020 and T022 land together.
- US3: needs T003 only.
- Polish: T024 after T005 and T016; T028 last.

## Parallel examples

- US1: T005 (component) beside T007 (AccountLayout), then T009, T010, T011 together.
- US2: T013, T017, T020, T021, T022 together once their targets exist.

## Strategy

MVP is Phases 2 and 3: the banner shows and links to Billing, without the dismiss control. US2 ships with it in the same pull request, since the request asks for dismissal. US3 adds tests only.
