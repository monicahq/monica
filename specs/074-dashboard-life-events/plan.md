# Implementation Plan: Life events on the vault dashboard

**Branch**: `2026-10-11-dashboard-life-events` | **Date**: 2026-10-11 | **Spec**: [spec.md](spec.md)

## Summary

The dashboard gets a section, `#contact-timeline`, holding the quiet row of design 1b, the contact page's life event panel built on the Me contact, and that contact's timeline, read only. The panel gains a `who` step reached through "Change". A new `VaultLifeEventController` opens the panel and records the life event with `contact_id` as its subject, through `CreateLifeEvent` tagged `Dashboard`. The request reading moves from `ContactLifeEventController` into a shared `SaveLifeEventRequest`. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel, Blade, Alpine.js and Alpine Ajax

**Primary Dependencies**: none added

**Storage**: no schema change. `life_events.created_from` is a `string(16)` (R7).

**Testing**: PHPUnit, `tests/Unit` and `tests/Feature/Controllers`

**Project Type**: web application with a JSON API, CardDAV and MCP

**Constraints**: the contact page must behave exactly as before; WCAG 2.2 AA on the panel's new step

**Scale/Scope**: 2 new controllers, 1 new Form Request, 3 routes, 1 enum case, 3 view models, 4 views, 1 script

## Constitution Check

| Principle | Result |
| --- | --- |
| I. Actions hold the logic | Pass. `CreateLifeEvent` records it; controllers read the request and redirect. |
| II. Simplicity | Pass. One panel and one script serve both screens (R1, R5); the Form Request replaces private methods rather than copying them (R3). |
| III. Tested | Pass, see Tests. |
| IV. Laravel conventions | Pass. One Form Request, justified by the controllers skill's own criterion (R3). |
| V. Localised | New copy through `monica:localize` in every locale. |
| VII. Stable core | Pass. Life events and the Me contact keep their nature. |
| IX. Accessible | The `who` step announces itself through the panel's caption, keeps focus on its search, and returns focus to the row's button on close (FR-011). |
| X. Through the API | Pre-existing gap: life events have no API or MCP tool (R9). This feature adds no capability the contact page lacks, so it does not widen the gap. |

Gate passes. Re-checked after design: unchanged.

## Changes

| File | Change |
| --- | --- |
| `app/Enums/LifeEventSourceEnum.php` | `Dashboard = 'dashboard'` |
| `app/Actions/CreateLifeEvent.php` | `createdFrom` parameter, default `Contact`; docblock says it may be written from the dashboard |
| `app/Http/Requests/SaveLifeEventRequest.php` | New. `rules()`, `lifeEventType()`, `values(?StructureVersion)`, `participants(Contact)`, moved from the contact controller; `getRedirectUrl()` sends a refused update to its edit URL |
| `app/Models/LifeEvent.php` | `confirmationParts(User $reader)`: the type, the date in the reader's format and the participants' first names, moved from the contact controller's `sentenceParts()` |
| `app/Http/Controllers/App/Vaults/ContactLifeEventController.php` | `create` and `update` take `SaveLifeEventRequest`; private helpers removed; behaviour unchanged |
| `app/Http/Controllers/App/Vaults/VaultLifeEventController.php` | New. `new` renders the dashboard with the panel open; `create` validates `contact_id`, finds it in the vault (404 otherwise), runs `CreateLifeEvent` with `createdFrom: Dashboard`, redirects to `vaults.show` with the contact page's status and `lifeEventJustAdded` |
| `app/Http/Controllers/App/Vaults/VaultLifeEventCandidateController.php` | New. `index` lists the vault's contacts but `except[]`, through `_life-event-candidates` (R4) |
| `app/Http/Controllers/App/Vaults/VaultController.php` | `show` passes the flashed `lifeEventJustAdded` to the view model |
| `routes/web.php` | `vaults.lifeEvents.new`, `vaults.lifeEvents.create`, `vaults.lifeEventCandidates.index` |
| `app/ViewModels/Vaults/VaultDashboardViewModel.php` | `meContact()`, `lifeEventForm()` and `timeline()` built on it, null without one (R8); empty copy of "Recent life events" no longer says "on their page" |
| `app/ViewModels/Vaults/LifeEventFormBlock.php` | `choosesSubject` flag: action, new URL and candidates URL point at the dashboard routes; projection adds `subject` (from `old('contact_id')`, else the contact) and the `who` caption |
| `app/ViewModels/Vaults/ContactTimelineBlock.php` | `isReadOnly` flag and `perPage` parameter, default `PER_PAGE` (R6) |
| `resources/views/app/vaults/show.blade.php` | The section of R2 at the top of the main column: the 2a switch, "Add a life event" or "All reminders" beside it, then the timeline panel (life event panel, timeline, "View full timeline") or the attention panel (R10) |
| `resources/views/app/vaults/_attention-dates.blade.php` | New. The attention panel, empty state included, with "All reminders" in its footer when asked; `_attention` wraps it in its section |
| `resources/views/app/vaults/contacts/_life-event-panel.blade.php` | `contact_id` hidden input and "For :name · Change" line when the subject is choosable; the `who` step; the first "Who was there" chip reads `subject.name` |
| `resources/views/app/vaults/contacts/_timeline.blade.php` | When read only: no Delete, no modal, a plain Edit link, and "View full timeline" in place of "Load more" |
| `resources/js/life-event-panel.js` | `subject`, the `who` step, `choose()` per R5, `except[]` includes the subject |
| `lang/*.json` | Through `monica:localize` only |

## Tests

| Test file | Proves |
| --- | --- |
| `tests/Unit/Actions/CreateLifeEventTest.php` | `createdFrom` is stored; default stays `contact` |
| `tests/Feature/Controllers/App/Vaults/VaultLifeEventControllerTest.php` | `new` answers with the panel; `create` records for the Me contact and redirects to the dashboard with the status; records for another contact with Michael as participant; a contact of another vault answers 404; a missing `contact_id` and a deactivated type come back as errors |
| `tests/Feature/Controllers/App/Vaults/VaultLifeEventCandidateControllerTest.php` | lists the Me contact and everybody but `except[]`; leaves out other vaults |
| `tests/Feature/Controllers/App/Vaults/ContactLifeEventControllerTest.php` | passes unchanged, which proves the move to the Form Request, refused update redirect included |
| `tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php` | the timeline lists only the Me contact's life events, ten at most, with the contact page as its full view; no Me contact gives no form and no timeline |
| `tests/Unit/ViewModels/Vaults/LifeEventFormBlockTest.php` | the subject defaults to the contact and is restored from `old('contact_id')`; the dashboard URLs when the subject is choosable |
| `tests/Unit/ViewModels/Vaults/ContactTimelineBlockTest.php` | a read only entry links to the contact page's edit; `perPage: 10` lists ten of twelve and offers no next page |
| `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php` | the dashboard renders "Something happened?" and no "Delete" on the timeline |

`RouteProtectionTest` covers the three routes on its own. The whole suite runs, since the shared panel and its script change.

## Project Structure

No new folder. `data-model.md` is omitted (one enum case, above), `contracts/` is omitted (three web routes, above), and `quickstart.md` is omitted: the Tests table is the validation guide.

## Complexity Tracking

None.
