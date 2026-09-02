# Implementation Plan: Reminder foundation on a date field

**Branch**: `2026-09-02-reminder-foundation` | **Date**: 2026-09-02 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/012-reminder-foundation/spec.md`

## Summary

One new table and one new model, the rule. It hangs off the field value it reads, which is what makes
a date inside a repetition work with no extra machinery and what caps it at one rule per value. The
options are drawn under the date control by a new component, submitted by the block's own form, and
written by the block's own save. Nothing is scheduled and nothing is delivered.

## Technical Context

| | |
| --- | --- |
| **Language/Version** | PHP 8.4, Laravel 12 |
| **Primary Dependencies** | None added. Blade, Alpine.js and Alpine Ajax are already in the stack; the zone list comes from `App\Helpers\TimezoneCatalogue`, built for feature 011. |
| **Storage** | MySQL. One new table, `reminders`. One existing migration edited, `create_fields_table`, to drop `is_reminder_source`, per the migrations skill's rule that migrations are altered rather than added until production. |
| **Testing** | PHPUnit. New unit tests for the model, five enums, five actions, one helper and one validation rule; a view test for the component; the existing `ContactSectionController` and `SetFieldValues` tests grow the reminder cases. |
| **Target Platform** | The logged-in web application, responsive. |
| **Project Type** | Laravel monolith, Blade rendered. |
| **Performance Goals** | The block's save stays one transaction. Drawing the options adds no query beyond the rules loaded with the section's values. |
| **Constraints** | The next date is computed on read, never stored. The zone is frozen on the row. The save writes the value before the rule that reads it, so the rule always has a value to point at. |
| **Scale/Scope** | One screen region, one table, five actions. No API surface, no queue, no schedule. |

## Constitution Check

*GATE: passed before Phase 0. Re-checked after Phase 1: unchanged, and the violation below is still the only one.*

| Principle | How this plan meets it |
| --- | --- |
| I. Actions hold the logic | Five actions, one operation each. `SetFieldValues` orchestrates and writes nothing itself, as it already does for values. The view model reads. |
| II. Simplicity over abstraction | One table, no second model for a moment, no service layer. The one abstraction added is `ReminderSchedule`, because the arithmetic is read by a screen now and by a job next. |
| III. Tested before merged | Every new class gets its test; the flipped `canDriveAReminder()` flips its existing assertion. |
| IV. Laravel conventions | `up()` only, foreign keys at the foot and never `constrained()`, one class per file, inline `$request->validate()` except where the existing form request already carries the block. |
| V. Localized and safe | Every string through `__()` or `trans_choice()`, then `php artisan monica:localize` and nine locales. Unticking the box is destructive of a rule, so it warns before the save takes it away. |
| VI. Nothing changes underfoot | The zone is frozen at creation, which is this principle's own worked example. Correcting the date recomputes nothing stored, because nothing is stored. |
| VII. Stable core | Reminders are one of the nine fixed concepts. No new way to attach user data: the rule reads a field value, which is the one personalisation mechanism. |
| VIII. Nothing disappears silently | Five reasons a rule goes quiet, each recorded on the row and shown in words. Untick is a soft delete. Reactivation is matched on the reason so one cause never undoes another. |
| IX. Accessible | The disclosure is a real checkbox with `aria-controls` and `aria-expanded`, the panel is toggled with `hidden`, every control is labelled, the moment phrasing is text rather than colour, and the reveal has no animation under reduced motion. |
| X. Everything through the API | **Violated.** See Complexity Tracking. |
| XI. The written record is short | `plan.md` within its 100 line cap, `research.md` within 80, `data-model.md` within 60. `contracts/` omitted because there is no interface; `quickstart.md` carries only the commands, since the spec's acceptance scenarios already carry the validation path. |

## Project Structure

### Documentation (this feature)

```text
specs/012-reminder-foundation/
├── plan.md              # This file
├── research.md          # Phase 0: the ten decisions and what each rules out
├── data-model.md        # Phase 1: the table, and the corrections to spec/models/040
├── quickstart.md        # Phase 1: the commands to run it and check it
└── tasks.md             # /speckit-tasks output, not created here
```

`contracts/` is deliberately absent: this feature exposes no interface to anything outside the Blade
screen. The reason it cannot yet is in Complexity Tracking.

### Source Code (repository root)

```text
app/
├── Actions/            CreateReminder, UpdateReminder, DestroyReminder,
│                       DeactivateReminders, ReactivateReminders
│                       edited: SetFieldValues, CreateField, UpdateField,
│                       ArchiveContact, UnarchiveContact, DestroyContact,
│                       RestoreContact, DestroyField, DetachFieldValues, ReattachFieldValues
├── Enums/              ReminderSourceEnum, ReminderCadenceEnum, ReminderMomentDirectionEnum,
│                       ReminderMomentUnitEnum, ReminderInactiveReasonEnum
│                       edited: FieldTypeEnum, UserActionEnum, DefaultFieldEnum
├── Exceptions/         ReminderNeedsADate
├── Helpers/            ReminderSchedule
│                       edited: StructureFileReader, StructureWriter, MarketplacePayload
├── Http/Requests/      edited: SaveFieldValuesRequest
├── Models/             Reminder, edited: Field, FieldValue, Contact
├── Rules/              ReminderMoments
└── ViewModels/Vaults/  edited: ContactViewModel

database/
├── factories/          ReminderFactory, edited: FieldFactory
└── migrations/         2026_09_02_000000_create_reminders_table.php
                        edited: 2026_08_09_000006_create_fields_table.php

resources/views/components/  field-reminder.blade.php, edited: field-input.blade.php
lang/                        nine files, through monica:localize
tests/                       Unit and Feature, mirroring the above
```

**Structure Decision**: the existing tree, unchanged. The reminder options are a component under the
date control rather than a screen of their own, because the rule is an answer about a date and is
saved by the block that asks it.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| Principle X, no API coverage for reminders | The API reaches status, auth, me, api keys, logs, sent emails and vaults. It does not reach contacts, fields or field values, and neither `fields` nor `field_values` carries a `uuid`, so a rule created from a value cannot be addressed from outside at all. | Giving both tables a public identifier and building contact addressing is a larger change than this feature, touching models 017 and 022 and the shape of the whole record API. It is its own feature, and this one leaves the debt the constitution already records under follow-up item 2. |
