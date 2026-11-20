# Implementation Plan: Withhold the visitor's network address from monitoring

**Branch**: `2026-11-20-nightwatch-ip-redaction` | **Date**: 2026-11-20 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/144-nightwatch-ip-redaction/spec.md`

## Summary

Every request trace sent to Laravel Nightwatch carries the client's address twice: in the record's
`ip` field and, because the instance sits behind Cloudflare, in the forwarding headers. The existing
`Nightwatch::redactRequests()` callback in `AppServiceProvider::configureNightwatch()`, which `boot()`
already calls, gains two things: `$request->ip = ''` and the removal of five forwarding headers. The
user resolver already sends only the id and is left as it is, with a test added so that stays proven.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: `laravel/nightwatch` v1.30.6

**Storage**: N/A, nothing is persisted

**Testing**: PHPUnit, `tests/Unit/Providers/AppServiceProviderTest.php`

**Target Platform**: Linux server behind Cloudflare

**Project Type**: Web application, single Laravel project

**Performance Goals**: N/A, two assignments and five header removals per traced request

**Constraints**: `Records\Request::$ip` is typed `string`, so it cannot be set to `null` (research R1)

**Scale/Scope**: One method in one provider, two tests

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Verdict |
| --- | --- |
| I. Actions hold the business logic | Not concerned. Monitoring configuration is framework wiring, already in the provider |
| II. Simplicity over abstraction | Pass. Lines added to the existing callback, no new class or helper |
| III. Tested before merged | Pass. Both requirements get a unit test through the existing `nightwatchRequest()` helper |
| IV. Laravel conventions are the default | Pass. Uses the package's documented redaction hook |
| V. Localized and safe user experience | Not concerned. No copy |
| VI. Nothing changes under anybody's feet | Pass. No stored data, no screen, no API change |
| VII. Stable core, unlimited personalisation | Not concerned |
| VIII. Nothing disappears silently | Pass. What disappears is the operator's monitoring data, and the change itself, the provider docblock and this spec say so |
| IX. Accessible to everybody | Not concerned. No markup |
| X. Everything goes through the API | Not concerned. Nothing a person can do changes |
| XI. The written record is short | Pass. The provider docblock gains one clause |

No violation. The post-design check gives the same result.

## Project Structure

### Documentation (this feature)

```text
specs/144-nightwatch-ip-redaction/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── request-trace.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
app/Providers/AppServiceProvider.php          # configureNightwatch(): blank ip, drop forwarding headers
tests/Unit/Providers/AppServiceProviderTest.php
```

**Structure Decision**: The change stays inside `configureNightwatch()`, next to the rules it joins.
That method is called from `boot()`, which answers the request to put this in `boot()` without
splitting the privacy rules across two places.

## Complexity Tracking

No violation to justify.
