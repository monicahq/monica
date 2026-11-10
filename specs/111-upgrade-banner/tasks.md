---

description: "Task list for the upgrade strip under the top bar"
---

# Tasks: Upgrade strip under the top bar

**Input**: Design documents from `specs/111-upgrade-banner/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/full-access-offer.md](contracts/full-access-offer.md)

**Tests**: Included. The constitution (III, non negotiable) requires every view model to have a test, and `.claude/rules/testing.md` decides the layer: the view model test owns the counts and tones, the component test owns the accessibility contract, the layout tests own where the strip shows. No test asserts a class string or a tone class.

**Organization**: Grouped by user story. US1 and US2 share one view model and one component, so US2 builds on US1 rather than standing apart.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: The user story from spec.md

---

## Phase 1: Setup

- [X] T001 Read `resources/views/components/full-access-offer.blade.php`, `resources/views/components/badge.blade.php`, `resources/views/components/icon-button.blade.php`, `resources/views/components/button/secondary.blade.php`, `app/ViewModels/NotificationBarViewModel.php` and `tests/Unit/ViewModels/NotificationBarViewModelTest.php` before writing anything, as `.claude/rules/components.md` asks

---

## Phase 2: Foundational

- [X] T002 Create `app/ViewModels/FullAccessOfferViewModel.php` with `php artisan make:class --no-interaction ViewModels/FullAccessOfferViewModel`: constructor takes `private readonly User $user`; class docblock of one or two sentences; one public method `meters()` returning `list<array{label: string, used: int, limit: int, percent: int, tone: string}>`, People first (`$account->contactCount()`, `$account->contactLimit()`), Vaults second (`$account->vaults()->count()`, `$account->vaultLimit()`). The limits are never null here because the layouts build this only when `User::isOfferedFullAccess()` is true; cast with `(int)` and say nothing more. `percent` is `min(100, (int) round($used / $limit * 100))`, and 100 when the limit is 0

**Checkpoint**: the view model exists; nothing renders it yet.

---

## Phase 3: User Story 1 - A free account sees how much of the free plan it uses (Priority: P1) 🎯 MVP

**Goal**: the strip of design 3b under the top bar of every screen that showed the old banner.

**Independent Test**: a free account with 25 of 30 people and 1 of 1 vault sees "Free plan", "People" "25 / 30", "Vaults" "1 / 1" and "Upgrade your account" on the vault list, a vault screen and a settings screen, and not on billing.

### Tests for User Story 1

- [X] T003 [P] [US1] Write `tests/Unit/ViewModels/FullAccessOfferViewModelTest.php` (`RefreshDatabase`, The Office names): `it_counts_the_people_the_free_plan_measures` (a Me contact and a partial contact are left out, the count equals `contactCount()`), `it_counts_the_vaults_of_the_account` (another account's vault is left out), `it_lists_people_before_vaults`
- [X] T004 [P] [US1] Rewrite `tests/Feature/Views/Components/FullAccessOfferTest.php`: build a user and pass `new FullAccessOfferViewModel(user: $user)` to `$this->blade('<x-full-access-offer :viewModel="$viewModel" />', ['viewModel' => ...])`. Assert per [contracts/full-access-offer.md](contracts/full-access-offer.md): `aria-label="Free plan usage"`, "Free plan", each `role="progressbar"` with its `aria-label`, `aria-valuemin="0"`, `aria-valuemax` and `aria-valuenow`, the text `25 / 30`, the link to `route('settings.billing.index')` reading "Upgrade your account"
- [X] T005 [P] [US1] In `tests/Feature/Views/Layouts/ShellTest.php` and `tests/Feature/Views/Layouts/AccountLayoutTest.php`, replace every assertion on "Subscribe now to lift the limits of the free plan!" with one on `aria-label="Free plan usage"`, keeping the billing exclusion test as it is

### Implementation for User Story 1

- [X] T006 [US1] In `app/View/Components/Shell.php` and `app/View/Components/AccountLayout.php`, replace `bool $offersFullAccess` with `?FullAccessOfferViewModel $fullAccessOffer = null`, built as `new FullAccessOfferViewModel(user: $user)` when `isOfferedFullAccess()` is true. Keep the billing route exclusion in `Shell` and its comment
- [X] T007 [US1] Rewrite `resources/views/components/full-access-offer.blade.php` per plan.md "Design notes": first line `{{-- --}}` saying what it is, `@var \App\ViewModels\FullAccessOfferViewModel $viewModel`, `@props(['viewModel'])`; a `<section aria-label="{{ __('Free plan usage') }}">` with `print:hidden`; `x-badge tone="info" class="uppercase tracking-wide"` reading "Free plan"; one meter per row of `meters()` with `role="progressbar"`, `aria-label`, `aria-valuemin="0"`, `aria-valuemax`, `aria-valuenow`, the fill width as the one inline style, and the count `{{ $meter['used'] }} / {{ $meter['limit'] }}`; then `ms-auto` holding `x-button.secondary size="small" :href="route('settings.billing.index')" data-turbo="true"` reading "Upgrade your account". Logical spacing only (`ms-`, `ps-`), so it mirrors in right to left
- [X] T008 [US1] In `resources/views/layouts/shell.blade.php` and `resources/views/layouts/account.blade.php`, remove `<x-full-access-offer />` from the content column and render `<x-full-access-offer :viewModel="$fullAccessOffer" />` directly after `</header>`, inside `@if ($fullAccessOffer !== null)`. In the account layout it goes before the breadcrumb strip
- [X] T009 [US1] Run T003, T004 and T005: `php artisan test --compact tests/Unit/ViewModels/FullAccessOfferViewModelTest.php tests/Feature/Views/Components/FullAccessOfferTest.php tests/Feature/Views/Layouts`

**Checkpoint**: the strip shows with neutral meters everywhere the old banner did.

---

## Phase 4: User Story 2 - The meter warns before the limit and says when it is reached (Priority: P1)

**Goal**: neutral, warning and danger tones per meter.

**Independent Test**: the tone of each meter at 23, 24, 30 and 34 of 30 and at a limit of 0.

### Tests for User Story 2

- [X] T010 [US2] Add to `tests/Unit/ViewModels/FullAccessOfferViewModelTest.php`, driving the people limit through `config(['monica.free_contact_limit' => ...])`: `it_stays_neutral_below_four_fifths_of_the_limit` (23 of 30), `it_warns_from_four_fifths_of_the_limit` (24 of 30), `it_turns_to_danger_at_the_limit` (30 of 30, percent 100), `it_caps_the_meter_above_the_limit` (34 of 30: used 34, percent 100, danger), `it_turns_to_danger_when_the_limit_is_zero` (percent 100, no division by zero)

### Implementation for User Story 2

- [X] T011 [US2] In `app/ViewModels/FullAccessOfferViewModel.php`, set `tone` per row: `'danger'` when `$limit === 0 || $used >= $limit`, `'warning'` when `$used * 5 >= $limit * 4`, `'neutral'` otherwise
- [X] T012 [US2] In `resources/views/components/full-access-offer.blade.php`, choose track, fill and count colours with one `match ($meter['tone'])` per element: neutral `bg-hairline` / `bg-ink` / `text-ink`, warning `bg-warning-edge` / `bg-warning` / `text-warning`, danger `bg-error-edge` / `bg-error` / `text-error`. No colour in a shared base string. Below `sm` the track shortens rather than hides, so the progress bar stays in the accessibility tree
- [X] T013 [US2] Run `php artisan test --compact tests/Unit/ViewModels/FullAccessOfferViewModelTest.php`

**Checkpoint**: 3b as drawn, minus the close button.

---

## Phase 5: User Story 3 - The old banner is gone (Priority: P2)

**Goal**: one banner about the plan per screen.

**Independent Test**: no screen renders "Subscribe now to lift the limits of the free plan!".

- [X] T014 [US3] `grep -rn "Subscribe now to lift" app resources tests` returns nothing, and `grep -rn "offersFullAccess" app resources` returns nothing
- [X] T015 [US3] Run `php artisan monica:localize` so the old sentence leaves every `lang/*.json`, then translate "Free plan usage", "People" and "Upgrade your account" in every configured locale it reports, following `.claude/rules/translations.md` (French `’` and spacing, the glossary) and the translations skill. Never edit keys by hand

---

## Phase 6: User Story 4 - Hiding the strip (Priority: P2)

**Goal**: the existing "hide for good" close button on the new strip.

**Independent Test**: a user who hid the banner, before or after this change, sees no strip on any screen.

- [X] T016 [P] [US4] Add to `tests/Feature/Views/Components/FullAccessOfferTest.php`: `it_hides_the_offer_for_good_through_a_named_control`, asserting `action="{{ route('fullAccessOffer.update') }}"`, `name="_method" value="put"` and `aria-label="Hide this offer for good"`
- [X] T017 [P] [US4] Add to `tests/Feature/Views/Layouts/ShellTest.php`: `it_leaves_the_offer_out_once_the_user_hid_it`, a user with `full_access_offer_dismissed_at` set sees no `aria-label="Free plan usage"` on a vault screen
- [X] T018 [US4] In `resources/views/components/full-access-offer.blade.php`, after the upgrade button, add `<x-form method="put" :action="route('fullAccessOffer.update')">` holding `<x-icon-button type="submit" icon="close" :label="__('Hide this offer for good')" />`, as the old banner had it
- [X] T019 [US4] Run `php artisan test --compact tests/Feature/Views tests/Feature/Controllers/App/FullAccessOfferControllerTest.php tests/Feature/Controllers/Api/MeFullAccessOfferControllerTest.php tests/Unit/Actions/MarkFullAccessOfferAsDismissedTest.php`

---

## Phase 7: Polish & cross-cutting

- [X] T020 Render the strip in a throwaway test (no browser tools) at 375 pixels and wide, light and dark, and check: one row under the bar, nothing scrolls sideways, the button reachable, the warning and danger counts readable against `--card` (4.5:1 for text, 3:1 for the track and fill). Delete the throwaway test
- [X] T021 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff and check their evidence before acting on it
- [X] T022 Run the whole suite: `PAO_DISABLE=true php artisan test --parallel --compact`
- [X] T023 `php artisan monica:localize --check`, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent` all report nothing
- [X] T024 Add the change to the feature list with the `feature-documentator` skill

---

## Dependencies & execution order

- **Setup (T001)** → **Foundational (T002)** → **US1** → **US2** → **US3** and **US4**, which are independent of each other → **Polish**.
- US2 edits the view model and component US1 wrote, so it waits for US1.
- US3 only removes and translates; T015 must run after every new string exists (after T007, T012 and T018 if US4 lands first).
- US4 touches the same component file as US1 and US2, so T018 waits for T012.

## Parallel opportunities

- T003, T004 and T005 are three different test files and can be written together.
- T016 and T017 are two different test files.

```text
Together, after T002:
  T003 tests/Unit/ViewModels/FullAccessOfferViewModelTest.php
  T004 tests/Feature/Views/Components/FullAccessOfferTest.php
  T005 tests/Feature/Views/Layouts/{ShellTest,AccountLayoutTest}.php
```

## Implementation strategy

- **MVP**: Phases 1 to 4 (US1 and US2). That is design 3b as drawn. Do not ship it alone: the old banner had a close button, and a strip without one undoes the choice of everybody who used it, so US4 lands in the same pull request.
- **One pull request**, scope `ui`, for example `feat(ui): upgrade strip`.
