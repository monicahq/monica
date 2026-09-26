# Research: Telegram Bot Setup From the Configuration Screen

## R1. Why the incoming address can go missing (User Story 2)

- Finding: `routes/web.php` declares `telegram.webhook` only when a token is configured as the routes
  load. `monica:setup` runs `route:cache` in production, so the route table is fixed at deploy time.
  A token saved on the screen later never gets a route until the next deploy.
- Decision: declare the route whenever the integration is installed. The controller answers 404
  while no token is in effect, reading configuration at request time.
- Rejected: clearing the route cache after a save (needs write access to `bootstrap/cache`, races
  running workers).

## R2. The secret check lets an empty secret through

- Finding: `abort_unless($header === config('services.telegram.webhook_secret'), 403)` passes when
  both are null, so a call without a header is accepted on an instance with a token and no secret.
- Decision: refuse with 403 when the configured secret is blank, then compare with `hash_equals()`.

## R3. Where the last registration is kept

- Decision: a new one-row table `telegram_webhook_registrations` (see data-model.md).
- Rejected: a row in `instance_settings` (the screen lists unknown names as orphans); the cache (not
  durable, forgotten on every deploy).
- Fingerprints are `Hash::make($value)`, compared with `Hash::check()`: salted and slow, so a database
  dump alone cannot be used to test guesses. The columns are 255 wide because the hashing driver is
  an operator setting and an argon2 hash is longer than a bcrypt one.

## R4. Where the prerequisite rule lives

- The rule "installed, token set, secret set, address usable" is asked by the screen (to disable the
  control and give the reason) and by the action (to refuse).
- Decision: one static method, `App\Helpers\TelegramWebhook::missingPrerequisite(): ?string`, returning
  the translated reason or null. Both callers use it. It is a helper, like `SsoProviderAvailability`,
  because the models skill keeps business logic out of a model.
- Rejected: a policy. `.claude/rules/policies.md` wants one when a rule is written twice, but a
  policy method needs a signed in `User`, and the console command registers with nobody signed in.
  The rule is a state of the instance's configuration, not something a person may or may not do.

## R5. Calling Telegram

- `setWebhook` with `url` and `secret_token`, then `getMe` on success to read `username` for FR-012
  (`https://t.me/<username>`). The check uses `getWebhookInfo` (`url`, `last_error_message`,
  `last_error_date`, `pending_update_count`). Telegram never returns the secret, so the check cannot
  compare it.
- Each call: `Http::connectTimeout(3)->timeout(6)`, so both calls of a registration finish in under
  15 seconds (SC-002). A `ConnectionException` becomes "Telegram could not be reached".
- Refusals keep Telegram's `description` verbatim. No translation table for Telegram's messages;
  the sentence around it is translated.
- Tests use `Http::fake()` and `Http::preventStrayRequests()`.

## R6. Local or plain HTTP addresses (FR-013)

- The address is `route('telegram.webhook')`, built from `APP_URL`. Refused before any call when the
  scheme is not `https`, or the host is `localhost`, `127.0.0.1`, `[::1]`, or ends in `.localhost` or
  `.test`. Private network ranges are left to Telegram's own refusal (resolving DNS here is not worth it).

## R7. Warning before replacing the token or the secret (FR-010)

- The screen's warning today is static per setting with two modes, `switchedOff` and `lowered`.
- Decision: give `TelegramBotToken` and `TelegramBotWebhookSecret` a warning and a third mode,
  `replaced` (any non-empty value typed). `ConfigurationViewModel` blanks the warning when no
  registration is current, so it only shows when it is true.
- Clearing either secret with the existing Clear control already asks first (`x-delete-action`).

## R8. Who performed it, from the console

- `LogAdministratorAction` already takes a nullable administrator and writes `Log::SYSTEM`.
- `UpdateInstanceSetting` uses its administrator only for the log, so its parameter becomes `?User`.
  The registration action reuses it to fill the bot address (FR-012) from either surface.

## R9. No API endpoint

- Spec 036 removed the configuration API on the author's instruction. Registering, checking and
  generating a secret follow the screen and the console. Recorded in plan.md, Complexity Tracking.
