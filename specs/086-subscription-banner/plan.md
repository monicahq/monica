# Implementation Plan: Banner offering full access to free accounts

**Branch**: `2026-11-01-subscription-banner` | **Date**: 2026-11-01 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/086-subscription-banner/spec.md`

## Summary

A free metered account sees a banner at the top of every signed in screen, linking to Billing, until
it dismisses it once for good. One timestamp on `users`, one action, one web route and one API
route, one component drawn by the two signed in layouts. Decisions: [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: Blade, Tailwind CSS 4; Laravel Cashier through `Account::hasFullAccess()`

**Storage**: `users.full_access_offer_dismissed_at`, nullable timestamp (R2)

**Testing**: PHPUnit: action, model method, both controllers, the component, both layouts

**Target Platform**: the existing web application and JSON API

**Project Type**: web application (single Laravel project)

**Performance Goals**: no query added for a dismissed, unmetered or self hosted account (R7)

**Constraints**: WCAG 2.2 AA; every string in every configured locale; no JavaScript added

**Scale/Scope**: two layouts, one component, two routes, one column

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. `MarkFullAccessOfferAsDismissed`, called by the web and API controllers |
| II. Simplicity | Pass. No table, helper, composer or JavaScript; the welcome flow is copied |
| III. Tested before merged | Pass. Every class listed below gets its test |
| IV. Laravel conventions | Pass. Create migration edited per the migrations skill (needs `migrate:fresh`) |
| V. Localized, warns before destroying | Pass with work: the new strings in every locale; dismissing destroys nothing |
| VIII. Nothing disappears silently | Pass. The banner goes only on the reader's request |
| IX. Accessible | Pass. Named region, real link and button, focus from the page load (R4) |
| X. Through the API | Pass. `PUT api/me/full-access-offer` and the field on `UserResource` (R5) |
| XI. Short record | Pass. Spec cut from 163 lines to under 120; no data model or contract file, the table below carries both |
| One product everywhere | Pass. One rule; a self hosted instance meets it for nobody (R1) |

Re-checked after design: unchanged.

## Interfaces

| Surface | Contract |
| --- | --- |
| Web | `PUT full-access-offer`, name `fullAccessOffer.update`, beside `welcome.update`; no body; redirect back |
| API | `PUT api/me/full-access-offer`, name `me.fullAccessOffer.update`, beside `me.welcome.update`; no body; 200 with `UserResource` |
| `UserResource` | adds `full_access_offer_dismissed_at`: Unix timestamp or null, as `welcomed_at` |
| Column | `users.full_access_offer_dismissed_at`, nullable, commented like `welcomed_at` |

Dismissing twice keeps the first moment and answers the same way.

## Project Structure

### Documentation (this feature)

```text
specs/086-subscription-banner/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
database/migrations/0001_01_01_000000_create_users_table.php   column added
app/Models/User.php                                             cast, @property, isOfferedFullAccess()
app/Actions/MarkFullAccessOfferAsDismissed.php                  new
app/Http/Controllers/App/FullAccessOfferController.php          new, update()
app/Http/Controllers/Api/MeFullAccessOfferController.php        new, update()
app/Http/Resources/UserResource.php                             new field
app/View/Components/Shell.php, AccountLayout.php                offersFullAccess
resources/views/components/full-access-offer.blade.php          new
resources/views/layouts/shell.blade.php, account.blade.php      draw the component
routes/web.php, routes/api.php                                  one route each
resources/docs/api/03-profile.php                               the endpoint and the field
lang/*.json                                                     through monica:localize
tests/Unit/Actions, tests/Unit/Models, tests/Feature/Controllers/{App,Api},
tests/Feature/Views/Components, tests/Feature/Views/Layouts     one file each where none exists
```

**Structure Decision**: the existing Laravel layout. `x-notice` describes itself as not a banner, so
the component reads `x-notice` and `x-button` first and reuses what fits rather than copying.

## Complexity Tracking

None.
