# Implementation Plan: Contact age and upcoming birthday

**Branch**: `2026-11-20-name-template-copy` (kept, per the standing instruction not to branch) | **Date**: 2026-11-20 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/143-contact-age-birthday/spec.md`

## Summary

Show a line under a contact’s name giving their age and, when their birthday falls within the next 7
days, a countdown sentence (design 1b). The 7 day window becomes one constant shared with the
dashboard and the vault reminder dates. A new `Birthday` helper owns the age and next anniversary
rules, replacing the private copy in `VaultReminderDates`; the API contact resource gains `age` and
`next_birthday_on`. Nothing is stored.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (latest)
**Primary Dependencies**: Blade, Tailwind CSS, Alpine Ajax (`x-target`, `x-merge`), Carbon
**Storage**: existing `field_values` rows; no migration
**Testing**: PHPUnit, `composer test:parallel`
**Target Platform**: web application and its JSON API, MCP through the API
**Project Type**: web application (single Laravel project)
**Performance Goals**: no extra query per row on the API contacts list (eager loaded); one query on
the contact page
**Constraints**: the reader’s time zone decides the day; every phrase translated in every shipped
locale; WCAG 2.2 AA
**Scale/Scope**: one header line, one helper, one relationship, two API attributes, one constant

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. Nothing is written, so no action. The rule is a helper read by view models and the API resource, as `VaultReminderDates` is today |
| II. Simplicity | Pass. One helper replaces a private copy; no new layer |
| III. Tested before merged | Pass. Helper, relationship, view model and API controller tests planned |
| IV. Laravel conventions | Pass. Relationship with conditions, resource attributes |
| V. Localized and safe | Pass. Counted phrases through `trans_choice()`, translated by Sonnet subagents; sensitive fields respected in the API |
| VI. Nothing changes under anybody’s feet | Pass. Additive API attributes; dashboard and reminder dates keep the same 7 days. Ages no longer shown for a month unknown birthday in two blocks: a correction, recorded in the changelog |
| VII. Stable core | Pass. Reads the core `birthday` field key, which templates already rely on |
| VIII. Nothing disappears silently | Pass. No data removed |
| IX. Accessible | Pass. The line is plain text, the icon decorative, the separator hidden |
| X. Through the API | Pass. `age` and `next_birthday_on` on the contact resource, documented |
| XI. Short written record | Pass |

Post-design re-check: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/143-contact-age-birthday/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── api-contact.md
│   └── contact-header.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/
├── Enums/ReminderOccurrenceCategoryEnum.php          # + UpcomingDays = 7
├── Helpers/Birthday.php                              # new: age(), next(), daysUntilNext(), isUpcoming()
├── Helpers/VaultReminderDates.php                    # DefaultDays reads the enum; anniversary rule moves to Birthday
├── Models/Contact.php                                # + birthday() MorphOne
├── Http/Resources/ContactResource.php                # + age, next_birthday_on
├── Http/Controllers/Api/ContactController.php        # eager load birthday.field on index
└── ViewModels/Vaults/
    ├── ContactViewModel.php                          # + birthdayLine()
    ├── ContactAges.php                               # excludes month unknown, computes through Birthday
    └── VaultDashboardAttention.php                   # DAYS_AHEAD replaced by the enum
resources/
├── views/app/vaults/contacts/_facts.blade.php        # wrapper holds the line, then the list
├── docs/api/06-contacts.php                          # documents the two attributes
└── docs/api-changelog/2026-11-20.php                 # one entry
lang/*.json                                           # new phrases, every locale
tests/
├── Unit/Helpers/BirthdayTest.php                     # new
├── Unit/Models/ContactTest.php                       # birthday() conditions
├── Unit/ViewModels/Vaults/ContactViewModelTest.php   # birthdayLine() states
└── Feature/Controllers/Api/ContactControllerTest.php # attributes and sensitive refusal
```

**Structure Decision**: existing single Laravel project; every file is an existing one or sits next to
its siblings.

## Delivery checklist (from `definition-of-done`)

- Translations: `monica:localize`, then Sonnet subagents for every locale; `--check` passes.
- API docs and API changelog updated; MCP needs no change (`.claude/rules/mcp.md`).
- Changelog entry (What’s new) for the age line, mentioning the month unknown correction.
- `vendor/bin/pint --dirty --format agent`, PHPStan, `composer test:parallel`.
- Accessibility reviewer over `_facts.blade.php`.

## Complexity Tracking

None.
