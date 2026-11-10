# Implementation Plan: Upgrade strip under the top bar

**Branch**: `2026-11-10-upgrade-banner` | **Date**: 2026-11-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/111-upgrade-banner/spec.md`

## Summary

Replace the information panel offering full access with design 3b: a thin strip under the top bar
showing the free plan's people and vault meters, coloured by how close each is to its limit, an
"Upgrade your account" button and the existing "hide for good" close button. The component keeps its
name, `x-full-access-offer`, and its dismissal; it gains a view model for the counts and moves from
inside the content column to directly under the header of both layouts.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major), Blade, Tailwind CSS 4, Alpine.js

**Primary Dependencies**: none added

**Storage**: none changed. Reads `users.full_access_offer_dismissed_at`, the account's contacts and vaults

**Testing**: PHPUnit, unit test for the view model, feature tests for the component and the two layouts

**Target Platform**: the web application, both themes, from 375 pixels wide

**Project Type**: web application (Laravel monolith)

**Performance Goals**: two `count` queries per screen, only for users who see the strip

**Constraints**: WCAG 2.2 AA; right to left; one primary button per screen; no class assertions in tests

**Scale/Scope**: one component rewritten, one view model added, two layout classes and two layout views edited, three locale strings added

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Check | Result |
| --- | --- | --- |
| I. Actions hold the business logic | No new operation. Hiding reuses `MarkFullAccessOfferAsDismissed`. The view model only reads | Pass |
| II. Simplicity | One view model, one component, no new abstraction. The meter is inline, being in one file | Pass |
| III. Tested before merged | View model unit test, component test, layout tests updated | Pass |
| IV. Laravel conventions | `make:class` for the view model; no migration | Pass |
| V. Localized and safe | Three new strings through `monica:localize`; `ms-auto` and logical spacing so the strip mirrors in right to left; hiding is reversible by nobody but harmless, and was not warned before either | Pass |
| VI. Nothing changes under anybody's feet | Users who hid the old banner keep it hidden | Pass |
| VII. Stable core | Not touched | Pass |
| VIII. Nothing disappears silently | A hidden strip leaves the same usage on the billing screen | Pass |
| Hosted and self hosted run the same product | The strip shows quotas, which the constitution allows to differ; it gates no capability | Pass |
| Accessibility as a delivery condition | Named region, progress bars with values, count written beside the colour, labelled close button | Pass |
| Flags | Not a capability being built, a replacement of a shipped banner. No flag | Pass |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/111-upgrade-banner/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── full-access-offer.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── ViewModels/
│   └── FullAccessOfferViewModel.php          # new: the two meters
└── View/Components/
    ├── Shell.php                             # bool $offersFullAccess → ?FullAccessOfferViewModel $fullAccessOffer
    └── AccountLayout.php                     # same

resources/views/
├── components/full-access-offer.blade.php    # rewritten: design 3b
└── layouts/
    ├── shell.blade.php                       # strip moves under </header>
    └── account.blade.php                     # strip moves under </header>

lang/*.json                                   # via php artisan monica:localize

tests/
├── Unit/ViewModels/FullAccessOfferViewModelTest.php       # new
└── Feature/Views/
    ├── Components/FullAccessOfferTest.php                 # rewritten
    └── Layouts/{ShellTest,AccountLayoutTest}.php           # assertion moves to the new region
```

**Structure Decision**: Follow `NotificationBarViewModel`, the existing top level view model for
something every signed in screen draws. The layouts keep deciding whether the offer shows, as they do
today, and hand the component its view model.

## Design notes

- **Strip**: `flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-hairline bg-card px-4 py-2.5 sm:px-6 print:hidden`.
- **Badge**: `x-badge tone="info" class="uppercase tracking-wide"`.
- **Meter**: label in `text-body`; a `w-30 h-1 rounded-full` track and fill; the count in
  `tabular-nums font-medium`. Track, fill and count colours per tone: neutral `bg-hairline` /
  `bg-ink` / `text-ink`, warning `bg-warning-edge` / `bg-warning` / `text-warning`, danger
  `bg-error-edge` / `bg-error` / `text-error`. The tone names its colours only in a `match`, never in
  a shared base string. The fill width is the one inline `style`, as on the billing screen. Below
  `sm` the track shortens to `w-16` rather than hiding, so the progress bar stays in the
  accessibility tree.
- **Actions**: `ms-auto flex items-center gap-2`, holding the upgrade button and the close form.
- **Contrast**: check the warning and danger counts against `--card` in both themes during the visual
  review. The fill and track are not the only carrier of state, so they need 3:1 as graphics, and the
  count text needs 4.5:1.

## Risks

- Specification 110 edits `layouts/shell.blade.php` on another branch. The strip only adds a line
  after `</header>`, so the conflict is small; rebase whichever merges second.
- Three feature tests assert the old sentence and will fail until updated, which is expected.

## Complexity Tracking

None.
