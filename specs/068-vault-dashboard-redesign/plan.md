# Implementation Plan: Vault dashboard redesign

**Branch**: `2026-10-02-dashboard` | **Date**: 2026-10-02 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/068-vault-dashboard-redesign/spec.md`

## Summary

`vaults/{vault}` is rebuilt after `Vault Dashboard.dc.html`: a header with the vault tile, name,
description, figures and "Add somebody"; a main column listing the dates needing attention, answerable
in place; a side column of favourite people, recently viewed people and recent life events. Consulting a
contact (web, API or MCP) now records `last_consulted_at`, which the API exposes. No migration and no new route.
Decisions: [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: Blade, Tailwind CSS 4, Alpine.js

**Storage**: no schema change; writes the existing `contacts.last_consulted_at`

**Testing**: PHPUnit (view model, job and resource unit tests; controller feature tests)

**Target Platform**: web, light and dark themes, right to left

**Project Type**: web application (single Laravel project)

**Performance Goals**: a fixed number of queries per block, none per row; eager load avatars,
participants and life event types

**Constraints**: WCAG 2.2 AA; every string in every configured locale; no JavaScript added

**Scale/Scope**: one screen, two new view model classes, one job, one API field

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. Answering reuses `AnswerReminderOccurrence`; the visit is a job like `RecordVaultVisit` |
| II. Simplicity | Pass. One extraction (`VaultDeadlines`), justified in R2; the answer partial is reused unchanged |
| III. Tested before merged | Pass. Each new view model method, the job, the controller dispatch and the API field get tests |
| V. Localized | Pass with work: new headings, empty lines and counted figures in every locale |
| VI. Nothing changes under anybody's feet | Pass. Reads only, apart from a timestamp nobody edits |
| VIII. Nothing disappears silently | Pass. Vault settings stay in the sidebar; no data is removed |
| IX. Accessible | Pass. One `h1`, a heading per section, bands named in text, answers keep their screen reader suffix |
| X. Through the API | Pass. Every block reads data the API already serves; `last_consulted_at` is added (R8) |
| XI. Short record | Pass. No data model, contract or quickstart file: the spec's independent tests are the validation |

Re-checked after design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/068-vault-dashboard-redesign/
├── spec.md
├── plan.md
├── research.md
└── tasks.md   (next step)
```

### Source Code (repository root)

```text
app/Jobs/RecordContactVisit.php                          # new, R1
app/Http/Controllers/App/Vaults/ContactController.php    # show() dispatches the job
app/Http/Controllers/Api/ContactController.php           # show() dispatches it too
app/Mcp/Tools/{GetContact,GetMeContact}.php              # and so do the two MCP reads
app/Http/Resources/ContactResource.php                   # last_consulted_at, R8
resources/docs/api/06-contacts.php                       # documents the field
app/ViewModels/Vaults/VaultDeadlines.php                 # new, R2: rows awaiting an answer
app/ViewModels/Vaults/VaultRemindersViewModel.php        # rows() reads VaultDeadlines
app/ViewModels/Vaults/VaultDashboardAttention.php        # new: the three bands, R3
app/ViewModels/Vaults/VaultDashboardViewModel.php        # figures, favourites, recentlyViewed,
                                                         # lifeEvents; unbuiltSections and
                                                         # settingsRows removed
resources/views/app/vaults/show.blade.php                # rewritten
resources/views/app/vaults/_attention.blade.php          # new partial, includes reminders._answers
lang/*.json                                              # through monica:localize
tests/Unit/Jobs/RecordContactVisitTest.php
tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php
tests/Unit/ViewModels/Vaults/VaultDashboardAttentionTest.php
tests/Unit/ViewModels/Vaults/VaultDeadlinesTest.php
tests/Feature/Controllers/App/Vaults/VaultControllerTest.php    # the screen renders
tests/Feature/Controllers/App/Vaults/ContactControllerTest.php  # show() queues the visit
tests/Feature/Controllers/Api/ContactControllerTest.php         # the field is in the structure
```

**Structure Decision**: existing folders only. The attention block is a small object next to the main
view model, as `MarketplaceStatistics` is. Copy keys left without a caller once `unbuiltSections()` and
`settingsRows()` go are dropped by `monica:localize`, not by hand.

## Complexity Tracking

None.
