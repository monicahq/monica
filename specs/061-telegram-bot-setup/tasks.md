---

description: "Task list for setting up the Telegram bot from the configuration screen"
---

# Tasks: Telegram Bot Setup From the Configuration Screen

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md),
[contracts/routes.md](contracts/routes.md), [quickstart.md](quickstart.md)

**Tests**: required (constitution III). Follow `.claude/rules/testing.md`: `declare(strict_types=1);`, `#[Test]`,
snake_case `it_...` names, named arguments, `RefreshDatabase` only with the database, The Office data, no comments.
Every Telegram call is faked: `Http::preventStrayRequests()` and `Http::fake(['api.telegram.org/*' => ...])`.
`phpunit.xml` sets `APP_URL=http://localhost`, which FR-013 refuses: tests that register call
`URL::forceScheme('https')` and `URL::forceRootUrl('https://monica.dundermifflin.com')` first. Build an administrator as
`tests/Feature/Controllers/App/Admin/InstanceSettingControllerTest.php` does (`administrator()`, with
`auth.password_confirmed_at` in the session).

**Nothing goes to the API**: no change to `routes/api.php`, `app/Mcp` or the API docs (contracts/routes.md).

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

---

## Phase 2: Foundational (blocks every story)

- [X] T001 Create `database/migrations/2026_09_26_000000_create_telegram_webhook_registrations_table.php` as data-model.md says: `id`, `user_id` unsigned big integer nullable, `url` string, `token_fingerprint` string(64), `secret_fingerprint` string(64), `registered_at` datetime, timestamps, a `comment()` on each column as `create_instance_settings_table` does, foreign key `user_id` → `users.id` `nullOnDelete()` at the bottom, `up()` only, no `constrained()` (migrations skill).
- [X] T002 Create `app/Models/TelegramWebhookRegistration.php` (models skill, full model docblock, no logic) with `#[Fillable]`, `registered_at` cast `immutable_datetime`, `user(): BelongsTo`, and `database/factories/TelegramWebhookRegistrationFactory.php`. Create `app/Helpers/TelegramWebhook.php` (as `SsoProviderAvailability`, research R4): static `fingerprint(string $value): string` = `hash_hmac('sha256', $value, (string) config('app.key'))` (research R3); static `incomingAddress(): string` = `route('telegram.webhook')`; static `missingPrerequisite(): ?string` returning, in this order, `__('Unavailable on this server, because its package is not installed.')` when `config('services.telegram.installed')` is not true, `__('Set the bot token first.')`, `__('Set the secret first.')` when either is blank, `__('Telegram only calls an HTTPS address it can reach from the internet, and :address is not one.', ['address' => ...])` when the address fails research R6, else null; static `outOfDateReasons(TelegramWebhookRegistration $registration): list<string>` naming `__('the instance address changed')`, `__('the bot token changed')`, `__('the secret changed')` against current configuration; static `isCurrent(?TelegramWebhookRegistration $registration): bool` = a row and no reason.
- [X] T003 [P] Write `tests/Unit/Helpers/TelegramWebhookTest.php`: each `missingPrerequisite()` branch (`config()->set(...)` per case, `http://localhost` and `https://monica.test` refused, `https://monica.dundermifflin.com` accepted); `it_is_current_when_nothing_changed`; one test per out-of-date reason; `it_never_stores_the_token_or_the_secret_in_clear` (fingerprint differs from the value, stable for the same value).
- [X] T004 [P] Add `case TelegramWebhookRegistered = 'telegram_webhook_registered';` to `app/Enums/AdministratorActionEnum.php` with the `description()` `'Registered the Telegram bot at :url'`, and cover it in `tests/Unit/Enums/AdministratorActionEnumTest.php` the way the other cases are covered.
- [X] T005 Change `$administrator` in `app/Actions/UpdateInstanceSetting.php` to `?User` (research R8); add `it_attributes_a_change_made_with_nobody_signed_in_to_the_system` to `tests/Unit/Actions/UpdateInstanceSettingTest.php` (`Queue::fake()`, `Queue::assertPushed(LogAdministratorAction::class, fn ($job) => $job->administrator === null)`).

---

## Phase 3: User Story 1 - Register the bot from the screen (P1) MVP

**Independent Test**: token and secret set, press Register, success shown, row written, log dispatched.

- [X] T006 [P] [US1] Write `tests/Unit/Actions/RegisterTelegramWebhookTest.php`: `it_tells_telegram_the_address_and_the_secret` (`Http::assertSent` on `setWebhook` with `url` and `secret_token`; `assertDatabaseHas('telegram_webhook_registrations', [...])` with the url and both fingerprints; `LogAdministratorAction` pushed with `TelegramWebhookRegistered` and `['url' => ...]`); `it_updates_the_registration_instead_of_adding_one`; `it_refuses_without_calling_telegram_when_a_prerequisite_is_missing` (`Http::assertNothingSent()`, `ValidationException` on `telegram`); `it_refuses_with_telegrams_reason` (fake `{"ok":false,"description":"Unauthorized"}` 401, message contains `Unauthorized`, no row); `it_says_telegram_could_not_be_reached` (`Http::fake(fn () => throw new ConnectionException)`); `it_records_the_console_as_the_author` (administrator null, `user_id` null).
- [X] T007 [US1] Create `app/Actions/RegisterTelegramWebhook.php` (actions skill): constructor `private readonly ?User $administrator`; `execute(): TelegramWebhookRegistration`. Refuses with `ValidationException::withMessages(['telegram' => ...])` when `missingPrerequisite()` answers; posts `setWebhook` through `Http::connectTimeout(3)->timeout(6)` (research R5) with `url` and `secret_token`; catches `ConnectionException` as `__('Telegram could not be reached. Try again in a moment.')`; a failed response as `__('Telegram refused it: :reason', ['reason' => $response->json('description', $response->body())])`; then fills the first row or a new one (`user_id`, `url`, both fingerprints, `registered_at` now), saves, dispatches `LogAdministratorAction` on `low`. Class docblock of one or two sentences.
- [X] T008 [US1] Rewrite `app/Console/Commands/SetupTelegramCommand.php` to call `new RegisterTelegramWebhook(administrator: null)->execute()`, catch `ValidationException` and print `collect($e->errors())->flatten()->first()` with `$this->components->error()` and return `FAILURE`, else print `Registered :url with Telegram.` and return `SUCCESS`. Keep the signature and the description.
- [X] T009 [P] [US1] Write `tests/Unit/Console/Commands/SetupTelegramCommandTest.php`: success exits 0 and writes the row with `user_id` null; a missing secret exits 1 with the reason; a Telegram refusal exits 1 with Telegram's description.
- [X] T010 [US1] Create `app/Http/Controllers/App/Admin/TelegramRegistrationController.php` with `create(Request $request): RedirectResponse` calling `RegisterTelegramWebhook` with `$request->user()`; success redirects to `admin.configuration.index` with `status` `__('Telegram now sends this instance what people say to the bot.')` and `status_description` naming the address; `ValidationException` is left to the framework (redirects back with the error on `telegram`).
- [X] T011 [US1] In `routes/web.php`, inside the `password.confirm:auth.confirmPassword.new` group of the admin block: `Route::post('admin/configuration/telegram/registration', [TelegramRegistrationController::class, 'create'])->name('admin.configuration.telegram.registration.create');`.
- [X] T012 [US1] Create `app/ViewModels/Admin/TelegramRegistrationPanel.php` (as `app/ViewModels/Marketplace/MarketplaceStatistics.php`): `unavailableReason(): ?string` from `missingPrerequisite()`, `address(): string`; add `telegram(): TelegramRegistrationPanel` to `app/ViewModels/Admin/ConfigurationViewModel.php`.
- [X] T013 [US1] Create `resources/views/app/admin/configuration/_telegram.blade.php` (views skill, first line comment and `@var`): an `x-page-section` titled `__('Telegram bot')` with a line saying what registering does, the address in `x-code-value`, a POST form with `@csrf` and an `x-button.secondary` "Register with Telegram" that, when unavailable, keeps `aria-disabled="true"`, `onclick="return false"` and `aria-describedby` on the reason paragraph (as the setting rows do), and `$errors->first('telegram')` shown with `role="alert"`. `@include` it in `resources/views/app/admin/configuration/index.blade.php` after the groups loop, passing `'panel' => $viewModel->telegram()`.
- [X] T014 [P] [US1] Write `tests/Feature/Controllers/App/Admin/TelegramRegistrationControllerTest.php`: registers and redirects with `status`; a Telegram refusal comes back as an error on `telegram`; `it_asks_for_a_credential_before_registering` (no confirmed password → `auth.confirmPassword.new`, `Http::assertNothingSent()`); a regular user gets not found. Add to `tests/Feature/Views/App/Admin/ConfigurationTest.php` one test that the panel shows `Register with Telegram` and, with the token cleared, `Set the bot token first.` referenced by `aria-describedby`.

**Checkpoint**: T003, T006, T009, T014 pass.

---

## Phase 4: User Story 2 - The instance answers Telegram whatever the value came from (P1)

- [X] T015 [US2] In `routes/web.php`, change the webhook condition to `if (config('services.telegram.installed'))` (research R1).
- [X] T016 [US2] In `app/Http/Controllers/App/Settings/Account/Notifications/TelegramWebhookController.php`: `abort_if(blank(config('services.telegram.token')), 404);` first; then refuse 403 when the configured secret is blank, the header is not a string, or `! hash_equals($secret, $header)` (research R2).
- [X] T017 [US2] Add to `tests/Feature/Controllers/App/Settings/Account/Notifications/TelegramWebhookControllerTest.php`: `it_answers_as_not_found_while_no_token_is_in_effect` (`config()->set('services.telegram.token', null)`); `it_accepts_a_token_saved_on_the_screen` (token null in config, then `InstanceSetting::factory()->create(['name' => 'telegram_bot_token', 'value' => 'screen-token'])` and `InstanceSettings::apply()`, the `/start` call connects); `it_refuses_a_call_when_no_secret_is_configured` (secret null, no header, 403).

---

## Phase 5: User Story 3 - Know whether the bot is still registered (P2)

**Independent Test**: register, change the secret, the panel says out of date and why; Check shows Telegram's address.

- [X] T018 [US3] Extend `TelegramRegistrationPanel`: `state(): string` (`unavailable`, `unregistered`, `outOfDate`, `registered`, per data-model.md), `registeredAt()` shown through `x-timestamp`, `registeredAddress()`, `outOfDateReasons()`; render each state as one sentence in `_telegram.blade.php` inside a `role="status"` paragraph.
- [X] T019 [P] [US3] Write `tests/Unit/ViewModels/Admin/TelegramRegistrationPanelTest.php`: one test per state, and the reasons listed when out of date.
- [X] T020 [US3] In `app/Enums/InstanceSettingEnum.php`, give `TelegramBotToken` and `TelegramBotWebhookSecret` the `warning()` `'Telegram stops reaching this instance until the bot is registered again.'` and `warnsWhen()` `'replaced'`; in `resources/views/app/admin/configuration/index.blade.php`, make `takesSomethingAway(next)` answer `String(next) !== ''` for mode `replaced`; in `app/ViewModels/Admin/ConfigurationViewModel.php` `setting()`, set `warning` to null for those two when `TelegramWebhook::isCurrent(TelegramWebhookRegistration::query()->first())` is false (research R7). Cover the enum in `tests/Unit/Enums/InstanceSettingEnumTest.php` and the blanking in `tests/Unit/ViewModels/Admin/ConfigurationViewModelTest.php`.
- [X] T021 [P] [US3] Write `tests/Unit/Actions/CheckTelegramWebhookTest.php`: returns Telegram's `url`, `matches` true for this instance and false for another, `lastError` from `last_error_message`; writes nothing (`assertDatabaseCount('telegram_webhook_registrations', 0)`); unreachable raises the same message as T007; refused when no token.
- [X] T022 [US3] Create `app/Actions/CheckTelegramWebhook.php`: `execute(): array{url: ?string, matches: bool, lastError: ?string}` calling `getWebhookInfo` with the T007 timeouts; refuses on `telegram` when the token is blank.
- [X] T023 [US3] Create `app/Http/Controllers/App/Admin/TelegramRegistrationCheckController.php` (`create()` flashes `telegram_check` with the action's array); route `Route::post('admin/configuration/telegram/check', ...)->name('admin.configuration.telegram.check.create')` in the admin group, outside `password.confirm`; in `_telegram.blade.php` a "Check with Telegram" form and, when `session('telegram_check')` exists, what Telegram holds, whether it is this instance, its last error, and `__('Telegram does not say which secret it holds, so this cannot confirm it.')`.
- [X] T024 [P] [US3] Write `tests/Feature/Controllers/App/Admin/TelegramRegistrationCheckControllerTest.php`: flashes `telegram_check`; unreachable comes back as an error on `telegram`.

---

## Phase 6: User Story 4 - Fewer values to find by hand (P3)

- [X] T025 [P] [US4] Write `tests/Unit/Actions/GenerateTelegramWebhookSecretTest.php`: stores a 64 hexadecimal character secret in `instance_settings` under `telegram_bot_webhook_secret`; replaces an existing one; the log carries no value.
- [X] T026 [US4] Create `app/Actions/GenerateTelegramWebhookSecret.php`: constructor `private readonly User $administrator`; `bin2hex(random_bytes(32))` passed to `UpdateInstanceSetting` for `InstanceSettingEnum::TelegramBotWebhookSecret`.
- [X] T027 [US4] Create `app/Http/Controllers/App/Admin/TelegramWebhookSecretController.php` (`status` `__('A new secret is set. It is never shown back.')`); route `admin.configuration.telegram.secret.create` inside `password.confirm`; in `_telegram.blade.php` a "Generate a secret" form, which asks first with the T020 warning when a secret is set and a registration is current (same Alpine `confirming` pattern as the setting rows).
- [X] T028 [P] [US4] Write `tests/Feature/Controllers/App/Admin/TelegramWebhookSecretControllerTest.php`: generates and redirects with `status`; asks for a credential first.
- [X] T029 [US4] In `RegisterTelegramWebhook`, after a successful registration, call `getMe` (T007 timeouts; a failed call leaves the registration standing). When `config('services.telegram.bot_url')` is blank, pass `https://t.me/{result.username}` to `UpdateInstanceSetting` for `TelegramBotUrl` with the same administrator. When it is set and differs, `execute()` exposes the reported address (public readonly property) and `TelegramRegistrationController` adds `__('Telegram reports the bot at :address, which differs from the bot address set here.')` to `status_description`; nothing new is stored. Add to `RegisterTelegramWebhookTest`: `it_fills_the_bot_address_when_none_is_set`, `it_leaves_a_bot_address_already_set`, `it_keeps_the_registration_when_telegram_does_not_describe_the_bot`.

---

## Phase 7: Polish

- [X] T030 Run `php artisan monica:localize`, translate every new string in every locale (glossary and `.claude/rules/translations.md` typography), then `php artisan monica:localize --check`.
- [X] T031 Add to `docs/FEATURES.md`: `* Admin: Register the Telegram bot, check it and generate its secret from the configuration screen.` and `* Fix: The Telegram address answers a token saved on the screen, and refuses a call when no secret is set.`
- [X] T032 Run `php artisan test --compact`, `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, and walk [quickstart.md](quickstart.md).

---

## Dependencies

- T001, T002 block every story; T004, T005 block T007.
- US1 (T006 to T014) and US2 (T015 to T017) are independent and ship together: US1 alone registers an address that may not answer.
- US3 needs T012 and T013 (the panel). US4 needs T007 (for T029) and T020 (the warning reused in T027).
- Every story edits `_telegram.blade.php` and `routes/web.php`: those tasks run one after another.
- Polish comes last.

MVP is US1 plus US2 (T001 to T017).
