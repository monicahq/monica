---

description: "Task list for 026-login-link-setting"
---

# Tasks: Magic link sign in behind one instance setting

**Input**: Design documents from `/specs/026-login-link-setting/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/magic-link-setting.md](contracts/magic-link-setting.md), [quickstart.md](quickstart.md)

**Tests**: included. Principle III makes a test a condition of merge, so the test is part of the story rather than an optional phase. No middleware here has a unit test; the routes it guards test it.

**Organization**: by user story. The whole feature is one pull request, and User Story 1 is shippable on its own.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: different files, no dependency on an unfinished task
- Three test files are each edited by several tasks, so those tasks run one after the other

---

## Phase 1: Setup

**Purpose**: a branch and a working tree that can run the suite.

- [X] T001 Create the branch `2026-09-09-login-link-setting` from an up to date `main`, renaming it with the spec directory if the name is changed
- [X] T002 Run `composer install` at the repository root, since this worktree has no `vendor/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: the setting that both the gate and the screen read. Only User Story 2 depends on it.

- [X] T003 Add `'magic_link_enabled' => (bool) env('MONICA_MAGIC_LINK_ENABLED', true)` with three lines of comment beside `magic_link_duration_minutes` in `config/monica.php:298`
- [X] T004 [P] Document `MONICA_MAGIC_LINK_ENABLED=true` beside `MONICA_MAGIC_LINK_DURATION_MINUTES` in `.env.example:86`, saying that `false`, `0` or an empty value turn it off and anything else leaves it on

**Checkpoint**: `php artisan config:show monica.magic_link_enabled` prints `true`.

---

## Phase 3: User Story 1 - One passwordless way in, everywhere (Priority: P1) 🎯 MVP

**Goal**: the direct sign in shortcut is gone from every instance, and the magic link works on every environment and host, as it does today.

**Independent Test**: on an instance whose environment is not local and whose host is not a development one, ask for a link for an existing account, follow it, and arrive signed in. The sign in screen no longer offers `Sign in as`, and `POST laravel-login-link-login` answers 404.

- [X] T005 [US1] Remove the `@env(config('login-link.allowed_environments'))` block in `resources/views/app/auth/login.blade.php:96-107`
- [X] T006 [US1] Remove `exampleAccountEmail()`, `exampleAccountName()` and the now unused `User` and `ExampleAccountSeeder` imports in `app/ViewModels/Auth/LoginViewModel.php:23-33`
- [X] T007 [P] [US1] Remove `it_offers_the_seeded_account_to_a_developer`, `it_offers_nothing_when_the_example_account_was_never_seeded` and their unused imports in `tests/Unit/ViewModels/Auth/LoginViewModelTest.php:58-75`
- [X] T008 [P] [US1] Delete `tests/Feature/Controllers/App/Auth/LoginLinkTest.php`
- [X] T009 [P] [US1] Delete `config/login-link.php`
- [X] T010 [US1] Run `composer remove spatie/laravel-login-link`, then confirm with `composer why spatie/laravel-package-tools` that nothing requires it any more, in `composer.json` and `composer.lock`
- [X] T011 [US1] Add `it_no_longer_offers_a_direct_sign_in` in `tests/Feature/Controllers/App/Auth/LoginControllerTest.php`: the sign in screen does not see `Sign in as`, and `$this->post('laravel-login-link-login', [...])` answers 404
- [X] T012 [US1] Add `it_works_in_production_on_any_host` in `tests/Feature/Controllers/App/Auth/MagicLinkControllerTest.php`: with `$this->app['env'] = 'production'` and `URL::forceRootUrl('https://monica.example.com')`, the request screen answers 200 and a request emails a link

**Checkpoint**: `php artisan route:list --path=laravel-login-link` lists nothing, `Sign in as` appears in no file under `resources/` or `app/`, and the suite is green.

---

## Phase 4: User Story 2 - The operator switches the magic link off (Priority: P1)

**Goal**: one setting turns the magic link off for the whole instance. The screen stops offering it and its three routes answer not found.

**Independent Test**: set the setting off, open the sign in screen and see no offer, then call the request screen, the submission and a link directly and get 404 each time with no email sent.

- [X] T013 [US2] Create `app/Http/Middleware/EnsureMagicLinkIsEnabled.php` shaped like `EnsureUserIsAdministrator.php` beside it: `abort(404)` when `config('monica.magic_link_enabled')` is false, with the `@param Closure(Request): (Response) $next` docblock
- [X] T014 [US2] Alias it as `'magic.link' => EnsureMagicLinkIsEnabled::class` in the alias array of `bootstrap/app.php`
- [X] T015 [US2] Wrap the three magic link routes in `Route::middleware(['magic.link'])->group(...)`, with a comment naming the setting, inside the guest group of `routes/auth.php:29-36`
- [X] T016 [P] [US2] Add `magicLinkIsEnabled(): bool` returning `(bool) config('monica.magic_link_enabled')` in `app/ViewModels/Auth/LoginViewModel.php`
- [X] T017 [US2] Wrap the box holding `Send me a link instead` in `@if ($viewModel->magicLinkIsEnabled())` in `resources/views/app/auth/login.blade.php:109-112`
- [X] T018 [US2] Add `it_says_whether_the_magic_link_is_enabled` in `tests/Unit/ViewModels/Auth/LoginViewModelTest.php`, flipping `config()->set('monica.magic_link_enabled', ...)` both ways
- [X] T019 [US2] Add `it_answers_not_found_on_every_route_while_the_setting_is_off` in `tests/Feature/Controllers/App/Auth/MagicLinkControllerTest.php`: GET and POST `send-magic-link` and a signed link all answer 404, and `Notification::assertNothingSent()`
- [X] T020 [US2] Add `it_does_not_spend_a_link_while_the_setting_is_off` in `tests/Feature/Controllers/App/Auth/MagicLinkControllerTest.php`: a link created while on, followed while off, answers 404, keeps `used_at` null, and `assertGuest()`
- [X] T021 [US2] Add `it_answers_not_found_before_checking_the_signature_while_off` in `tests/Feature/Controllers/App/Auth/MagicLinkControllerTest.php`: an unsigned link answers 404, not 403
- [X] T022 [US2] Add `it_hides_the_magic_link_offer_while_the_setting_is_off` in `tests/Feature/Controllers/App/Auth/LoginControllerTest.php`: `assertDontSee('Send me a link instead')`
- [X] T023 [P] [US2] Say four middlewares, name `magic.link`, and drop `set.locale`, which is appended to the web group rather than aliased, in the first bullet of `.claude/skills/middlewares/SKILL.md`
- [X] T024 [P] [US2] Correct the same sentence in `.claude/CLAUDE.md:71`

**Checkpoint**: `php artisan route:list --name=magicLink` shows `magic.link` on all three routes, and the suite is green with the setting on and off.

---

## Phase 5: User Story 3 - Nobody learns whether an address has an account (Priority: P2)

**Goal**: a request for an unknown address and one for an existing address get the same answer, and nothing is created. The behaviour exists; this pins it.

**Independent Test**: request a link for an existing address and for an unknown one, and compare the two answers.

- [X] T025 [US3] Extend `it_says_the_same_thing_when_the_address_has_no_account` in `tests/Feature/Controllers/App/Auth/MagicLinkControllerTest.php:73` to assert the `users` count is unchanged and `Notification::assertNothingSent()`

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T026 Run `php artisan monica:localize` and confirm it reports nothing missing and nothing stale, since no string was added and the removed label was never translated
- [X] T027 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse`
- [X] T028 Run `php artisan test --compact` in full, and let CI run it on SQLite, MySQL and PostgreSQL
- [X] T029 Walk [quickstart.md](quickstart.md) by hand: the setting absent, `false`, `0`, `maybe`, and a link emailed before switching off

---

## Dependencies & Execution Order

### Phase Dependencies

- Phase 1 comes first.
- Phase 2 blocks Phase 4 only. Phases 3 and 5 need nothing but Phase 1.
- Phases 3, 4 and 5 are independent of each other.
- Phase 6 depends on every phase before it.

### Task Dependencies

- T005, T006 and T009 block T010: the view, the view model and the config file reference the package until they are gone.
- T003 blocks T013, T015 and T016: without the key, `config()` returns null and the gate reads as off.
- T013 blocks T014, which blocks T015. T016 blocks T017 and T018.
- T007 and T018 both edit `LoginViewModelTest.php`. T011 and T022 both edit `LoginControllerTest.php`. T012, T019, T020, T021 and T025 all edit `MagicLinkControllerTest.php`. Each pair or group runs in order.

### Parallel Opportunities

- T003 with T004.
- T007, T008 and T009 together, once T005 and T006 are done.
- T013 with T016, then T023 with T024.

---

## Implementation Strategy

### MVP

Phase 1, then Phase 3. The shortcut is gone and the magic link is always on, which is what every
instance has today. It ships on its own.

### Incremental delivery

Phase 2 and Phase 4 add the setting behind the magic link. Phase 5 pins the neutral answer that
already exists. Phase 6 closes.

### Order inside Phase 4

Middleware, alias, routes, then the screen, then the tests. The three route tests fail until T015
lands and pass without any change to the controller.
