# Research: Withhold the visitor's network address from monitoring

## R1. How to blank the address

- **Decision**: `$request->ip = ''`.
- **Rationale**: `Laravel\Nightwatch\Records\Request::$ip` is declared `public string $ip`. Assigning
  `null` raises a `TypeError` inside the redaction callback. `RequestSensor` itself builds the record
  with `ip: $request->ip() ?? ''`, so an empty string is already what the service receives for a
  request without an address.
- **Alternatives considered**: `null` as in the original snippet, which fails. A placeholder such as
  `0.0.0.0` or `***`, which could be read as a real address or break a filter on the service side.
  A hash or a truncated address, which nobody asked for and which keeps a correlation key.

## R2. Whether headers leave with the trace

- **Decision**: Remove `X-Forwarded-For`, `X-Real-IP`, `Forwarded`, `CF-Connecting-IP` and
  `True-Client-IP` from `$request->headers` in the same callback.
- **Rationale**: `RequestSensor` serialises `$record->headers` into the payload under `headers`. The
  package redacts only `Authorization`, `Cookie`, `Proxy-Authorization` and `X-XSRF-TOKEN` by default
  (`NIGHTWATCH_REDACT_HEADERS`), and redaction there masks a value rather than removing the header.
  The instance runs behind Cloudflare (`bootstrap/app.php` replaces `TrustProxies` with
  `Monicahq\Cloudflare\Http\Middleware\TrustProxies` and trusts `X-Forwarded-For`), so
  `CF-Connecting-IP` and `X-Forwarded-For` are present on every production request. The callback
  already removes `X-Telegram-Bot-Api-Secret-Token` the same way.
- **Alternatives considered**: Adding the names to `NIGHTWATCH_REDACT_HEADERS`. That depends on an
  environment variable every self hosted instance would have to set, and keeps the header name in
  the trace. Code applies everywhere, and it keeps every privacy rule in one method.

## R3. The user resolver

- **Decision**: Leave `Nightwatch::user(fn (Authenticatable $user): array => ['id' => $user->getAuthIdentifier()])` as it is. Add a test.
- **Rationale**: `UserProvider::resolvedDetails()` returns `['id' => $id, ...$resolver($user)]`.
  Returning `[]` and returning `['id' => $id]` produce the same array. Without a resolver the package
  would add `name` and `username` (the email), which is what the snippet guards against and what the
  existing resolver already prevents. No test covers it today.
- **Alternatives considered**: Replacing it with `fn ($user) => []`. Same output, one diff more, and
  it loses the type on the parameter.

## R4. Where the code goes

- **Decision**: `AppServiceProvider::configureNightwatch()`.
- **Rationale**: `boot()` calls it, so the request to configure this in `boot()` is met. Every other
  Nightwatch rule (rejections, sampling, the four redactions) lives there.

## R5. Changelog entry

- **Decision**: None.
- **Rationale**: The changelog skill excludes changes nobody can see. Nothing changes on any screen.
  The pull request description states the privacy change.
