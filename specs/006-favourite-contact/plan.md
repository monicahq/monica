# Implementation Plan: Favourite a Contact From Their Page

**Branch**: `006-favourite-contact` | **Date**: 2026-08-31 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/006-favourite-contact/spec.md`

## Summary

Turn the star already drawn beside the contact avatar into a control that sets and clears the
favourite mark, and show it for every contact rather than only for one already marked.

Almost everything this needs exists. `contacts.is_favourite` is a column, `FavouriteContact` and
`UnfavouriteContact` are written and tested, both write the activity entry, both refuse a contact in
another account, and `ContactViewModel::isFavourite()` already reads the state. The people list
already orders favourites first and already filters on them. What is missing is a route to reach
those two actions, a controller that is transport and nothing else, and a star that is a button
instead of an `aria-hidden` decoration.

The whole change is one route, one controller, one glyph, a handful of lines in one Blade file, two
accessors on an existing view model, and the tests for them. No migration, no model change, no new
action, no new folder.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 13.29, Blade, Tailwind CSS, Alpine.js, Alpine Ajax, Turbo

**Storage**: the existing `contacts.is_favourite` boolean. No migration, no new column, no new table.

**Testing**: PHPUnit. Feature tests for the controller and the rendered star, unit tests for the view
model accessors. `FavouriteContactTest` and `UnfavouriteContactTest` already cover the two actions
and are not touched.

**Target Platform**: server rendered web application, desktop, tablet and phone

**Project Type**: web application, single Laravel codebase

**Performance Goals**: one row update and one queued activity entry per press. The page redraw
replaces one region, not the screen.

**Constraints**: WCAG 2.2 AA on the contact page; the suite passes on SQLite, MySQL and PostgreSQL,
so no database specific SQL; PHPStan level 5 clean; every new string translated in all nine locales;
the control works without JavaScript.

**Scale/Scope**: one boolean per contact, uncapped. One screen touched.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Verdict | How this plan satisfies it |
|-----------|---------|----------------------------|
| I. Actions hold the business logic | PASS | `FavouriteContact` and `UnfavouriteContact` already exist and are reused unchanged. The new controller validates one boolean and delegates. The view model reads and holds no logic. |
| II. Simplicity over abstraction | PASS | No new abstraction. The controller follows `TemplateActivationController`, the closest existing precedent for a boolean a user flips, line for line in shape. |
| III. Tested before merged | PASS | Feature test for the controller including both compartment refusals, view test for the star in both states, unit test for the two new view model accessors. |
| IV. Laravel conventions | PASS | One class per file, typed signatures, array notation validation, camelCase route name, kebab-case URL, early returns, no `else`, `#[Override]` not needed here. |
| V. Localized and safe | PASS | Two new strings, run through `monica:localize` and translated in all nine locales. No confirmation step, and none is owed: clearing a mark destroys nothing. |
| VI. Nothing changes under anybody's feet | PASS | The mark is a property of the contact, not of the template version it renders from. No version is created, no snapshot is owed, and nothing anybody else sees changes, because an account is one person. The stale page case is handled by sending the desired state rather than a blind toggle. See research decision 3. |
| VII. A stable core | PASS | Contact is one of the nine fixed concepts and gains no new nature. The favourite mark is an existing property, not a new parallel way to attach user data. |
| VIII. Nothing disappears silently | PASS | Nothing is deleted. Both directions are recorded in the append only activity, naming the contact. |
| IX. Accessible to everybody | PASS, and it is the substance of the work | The star becomes a real button with `aria-pressed`, an accessible name that says what pressing it does, keyboard operability, a visible focus ring, and two states told apart by fill rather than by tint. See research decision 2. |
| X. Everything goes through the API | **PARTIAL, justified** | No API endpoint ships in this feature. See research decision 1 and the Complexity Tracking table below. |

**Product invariants**: mono-user, so a favourite belongs to the account and not to a member. The
contact stays in exactly one vault, and the vault check is enforced. The Me contact may be
favourited like any other; nothing here deletes or merges it.

**Repository layout**: no new top-level folder. Every file below sits in a folder the constitution
already names.

**Post-design re-check**: unchanged. Phase 1 introduced no new dependency, no new folder, no new
model, and no schema change. The one deviation is the API coverage, tracked below and unchanged by
the design.

## Project Structure

### Documentation (this feature)

```text
specs/006-favourite-contact/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── favourite-a-contact.md
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2 output, created by /speckit-tasks
```

### Source Code (repository root)

```text
routes/
└── web.php                                              # one route added beside contacts.avatar.update

app/
├── Actions/
│   ├── FavouriteContact.php                             # exists, reused unchanged
│   └── UnfavouriteContact.php                           # exists, reused unchanged
├── Http/Controllers/App/Vaults/
│   └── ContactFavouriteController.php                   # new, transport only
└── ViewModels/Vaults/
    └── ContactViewModel.php                             # isFavourite() exists; add the url and the label

resources/views/
├── app/vaults/contacts/
│   └── show.blade.php                                   # the star becomes a control
└── components/
    └── icon.blade.php                                   # add the filled star glyph

tests/
├── Feature/Controllers/App/Vaults/
│   └── ContactFavouriteControllerTest.php               # new
├── Feature/Views/
│   └── (star rendering covered in the controller test's rendered output)
└── Unit/ViewModels/Vaults/
    └── ContactViewModelTest.php                         # extended
```

**Structure Decision**: single Laravel codebase, existing folders only. The controller goes in
`app/Http/Controllers/App/Vaults/` beside `ContactAvatarController` and `ContactNameController`,
which are the two existing controllers that do one narrow thing to a contact from its own page. The
route goes in `routes/web.php` in the contact block, next to `contacts.avatar.update`. No `Api`
namespace work, per the tracked deviation.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Principle X, no API coverage for this capability | The product has no contacts API surface at all. `routes/api.php` reaches API keys, logs, sent emails, the current user and status. Exposing one boolean would mean settling how a contact is addressed by the API and what a contact resource returns, decided to serve a single flag rather than the resource. | Building the contacts API inside this feature was rejected as scope the user did not ask for and as the wrong moment to fix the shape of the largest resource in the product. The constitution's own workflow section provides the escape and names the price: the logic sits in an action and the controller adds nothing but transport, so the endpoint is added later with no rework. That condition is met exactly here. Recorded as a follow up in research decision 1. |
