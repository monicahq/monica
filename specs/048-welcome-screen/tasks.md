---
description: "Task list for the welcome screen"
---

# Tasks: Welcome screen

**Input**: Design documents from `specs/048-welcome-screen/`

**Prerequisites**: plan.md, spec.md, research.md, quickstart.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, The Office data, no comments, no class string or markup assertion. Each rule is tested once, in the layer that owns it.

**Organization**: the column and the action are the foundation. US1 builds the screen and the detour, US2 only proves the screen stays open, US3 is the API. US2 and US3 each need only the foundation and US1's routes.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency or configuration change.

## Phase 2: Foundational

- [X] T001 In `database/migrations/0001_01_01_000000_create_users_table.php`, add after `last_saved_at`: `$table->datetime('welcomed_at')->nullable()->comment('when the user pressed Let me in on the welcome screen, null until then');` (research R2)
- [X] T002 In `app/Models/User.php`, add `@property Carbon|null $welcomed_at` to the class docblock beside `last_saved_at`, and cast `'welcomed_at' => 'datetime'` in `casts()`; do not add it to `#[Fillable]`
- [X] T003 In `database/factories/UserFactory.php`, set `'welcomed_at' => now()` in `definition()` and add a `notWelcomed()` state setting it to null, beside `unverified()` (research R3)
- [X] T004 Create `app/Actions/MarkUserAsWelcomed.php` (`private readonly User $user`; one class docblock sentence; `execute(): User`): when `welcomed_at` is already set, return the user unchanged; otherwise set it to `now()`, save, return the user. No `LogUserAction` (research R4)
- [X] T005 [P] Create `tests/Unit/Actions/MarkUserAsWelcomedTest.php`: a `notWelcomed()` user gets `welcomed_at` near now (`assertEqualsWithDelta`); a user welcomed a month ago keeps that moment
- [X] T006 [P] In `tests/Unit/Actions/CreateAccountTest.php`, add a test that the user a new account creates has `welcomed_at` null

**Checkpoint**: `migrate:fresh` runs; T005 and T006 pass.

## Phase 3: User Story 1 - Be welcomed once (Priority: P1)

**Goal**: a new user meets the screen once at the home address and leaves it with Let me in.

**Independent Test**: spec US1.

- [X] T007 [US1] Create `app/ViewModels/Welcome/WelcomeViewModel.php` (`private readonly User $user`), modelled on `GettingStartedViewModel`: `caption()` → `__('Welcome to :app', ['app' => config('app.name')])`; `greeting()` → `__('Hi :name, we’re glad you’re here.', ['name' => explode(' ', $this->user->first_name)[0]])`; `introduction()` → `__('A place to remember the little things about the people who matter to you.')`; `letter(): array<int, string>` → the three paragraphs of `Welcome.dc.html`, verbatim; `signatories()` → `__(':first and :second', ['first' => 'Regis', 'second' => 'Alexis'])`; `signatoriesRole()` → `__('The people behind :app', ['app' => config('app.name')])`; `portraitUrls(): array<int, string>` → `asset('images/regis.webp')`, `asset('images/alexis.webp')`; `dismissUrl()` → `route('welcome.update')`
- [X] T008 [P] [US1] Create `tests/Unit/ViewModels/Welcome/WelcomeViewModelTest.php`: the greeting names the first word of a two word first name ("Michael Gary" reads "Hi Michael"), and `dismissUrl()` points at `welcome.update`
- [X] T009 [US1] Create `app/Http/Controllers/App/WelcomeController.php`: `index(Request): View` renders `app.welcome.index` with `viewModel`; `update(Request): RedirectResponse` runs `new MarkUserAsWelcomed(user: $request->user())->execute()` and redirects to `home.index`
- [X] T010 [US1] In `routes/web.php`, inside the `['auth', 'active', 'verified:auth.verification.notice']` group, right after `gettingStarted.index`, add `Route::get('welcome', [WelcomeController::class, 'index'])->name('welcome.index');` and `Route::put('welcome', [WelcomeController::class, 'update'])->name('welcome.update');`, with the `use` line
- [X] T011 [US1] Create `resources/views/app/welcome/index.blade.php` after direction 1a (research R6): first line `{{-- The letter a new account reads once, before anything else. --}}`, then the `@var` line; `<x-guest-layout :title="$viewModel->greeting()">`; a `<main>` centred, `max-w-160`, padded top and sides; centred header with `<x-logo :size="32" />`, `<x-caption>`, `<h1>` greeting, the introduction; `<x-panel>` with the paragraphs, a top hairline, two `<x-image>` portraits overlapped, `alt` empty, the signatories and their role; below, `<x-form method="put" :action="$viewModel->dismissUrl()">` holding `<x-button>` "Let me in" with the `arrow-right` icon (read `x-button`, `x-panel`, `x-image` and `x-icon` first). Tailwind utilities only, `text-pretty` on prose, nothing that stops reflow at a phone width
- [X] T012 [US1] In `app/Http/Controllers/App/HomeController.php`, after the `null` check and before the vault check: `if ($user->welcomed_at === null) { return redirect()->route('welcome.index'); }` (research R1)
- [X] T013 [P] [US1] In `tests/Feature/Controllers/App/HomeControllerTest.php`, add `it_sends_somebody_who_has_not_been_welcomed_to_the_welcome_screen` with a `notWelcomed()` user who holds a vault
- [X] T014 [US1] Create `tests/Feature/Controllers/App/WelcomeControllerTest.php`: the screen renders "Hi Pam, we’re glad you’re here." and "Let me in" and the `welcome.update` form action; `put` marks a `notWelcomed()` user and redirects to `home.index`

**Checkpoint**: US1 by hand, quickstart rows 1 to 3.

## Phase 4: User Story 2 - Read it again (Priority: P2)

**Goal**: the screen never refuses somebody already welcomed.

**Independent Test**: spec US2.

- [X] T015 [US2] In `tests/Feature/Controllers/App/WelcomeControllerTest.php`, add: a welcomed user gets the screen with 200; their `put` redirects to `home.index` and leaves `welcomed_at` as it was

## Phase 5: User Story 3 - Dismiss it from another application (Priority: P3)

**Goal**: the API reads and sets the moment.

**Independent Test**: spec US3.

- [X] T016 [P] [US3] In `app/Http/Resources/UserResource.php`, add `'welcomed_at' => $this->welcomed_at?->timestamp,` after `contact_list_direction`
- [X] T017 [US3] Create `app/Http/Controllers/Api/MeWelcomeController.php` like `MeContactListViewController`: `update(Request): UserResource` runs `MarkUserAsWelcomed` and returns `new UserResource($request->user()->refresh())`
- [X] T018 [US3] In `routes/api.php`, after `me.contactListView.update`: `Route::put('me/welcome', [MeWelcomeController::class, 'update'])->name('me.welcome.update');`, with the `use` line
- [X] T019 [US3] In `resources/docs/api/03-profile.php`, add `welcomed_at` to the example user (a timestamp) and to the field list ("When the user dismissed the welcome screen, as a count of seconds, or null if they have not."), and an endpoint entry `me-welcome-update`, `PUT /me/welcome`, "Dismiss the welcome screen", after `me-contact-list-view-update` and shaped like it; say that a second call keeps the first moment
- [X] T020 [US3] Create `tests/Feature/Controllers/Api/MeWelcomeControllerTest.php` modelled on `MeContactListViewControllerTest.php`: `json('PUT', ...)` on a `notWelcomed()` user is OK, has the user structure, and `data.attributes.welcomed_at` is set; the user is refreshed and marked

**Checkpoint**: quickstart last row; `ApiDocsReturnFieldsTest` passes.

## Phase 6: Polish

- [X] T021 Run `php artisan monica:localize`, translate every reported string in every configured locale following `.claude/rules/translations.md` (French typography, glossary), then `php artisan monica:localize --check`
- [X] T022 Run the quickstart's automated block, then the full suite `php artisan test --compact` (the factory default touches every test building a user)
- [ ] T023 Walk the quickstart's by hand rows, keyboard and 200 per cent zoom included

## Dependencies

- T001 → T002 → T003 → T004 → T005, T006. Phase 2 blocks every story.
- US1: T007 → T008; T007, T010 → T009 → T011 → T014; T012 → T013.
- US2 needs T014. US3 needs T004 only, and T016 → T017 → T018 → T019 → T020.
- Polish after all stories.

## Parallel execution

- After T004: T005, T006, T007, T016 in parallel.
- In US1: T008 and T013 beside T011.

## Implementation strategy

MVP is Phase 2 plus US1: the screen, once. US2 is one test. US3 closes principle X and can ship in the same pull request.
