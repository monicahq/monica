# Implementation Plan: Help bubbles

**Branch**: `2026-10-27-help-bubbles` | **Date**: 2026-10-27 | **Spec**: [spec.md](spec.md) | **Research**: [research.md](research.md)

## Summary

Rewrite `x-help` as design 3c with a disclosure contract (R1, R4), link it to portal pages by id resolved per reader (R2), enforce existing guides with a coverage test (R3), write a `help-bubbles` skill, and put one bubble on the profile screen's email section (R9).

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13, Blade, Alpine.js 3 (`@alpinejs/focus` already installed)

**Primary Dependencies**: none added

**Storage**: none. Guides are the Markdown files under `docs/portal`

**Testing**: PHPUnit, `$this->blade()` for the component, a glob coverage test, `PortalCatalogueTest` for the lookup

**Target Platform**: the web application, every screen that calls `x-help`

**Project Type**: Laravel web application

**Performance Goals**: one catalogue scan per bubble rendered (R2)

**Constraints**: WCAG 2.2 AA (spec FR-A); every string through `__()` and translated in every locale

**Scale/Scope**: 1 component, 1 slot on `x-page-section`, 1 static method, 2 call sites, 1 skill, 0 new portal pages

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold business logic | Pass. No business rule; the lookup is presentation over static files |
| II. Simplicity | Pass. One component rewritten, one method added, no new class |
| III. Tested before merged | Pass. Component test, coverage test, catalogue test (see Tests) |
| IV. Laravel conventions | Pass. Anonymous component with `@props`, named slot |
| V. Localized | Pass. Bubble copy translated in every locale; `monica:localize --check` |
| VI. Nothing changes under anybody's feet | Pass. No data, no route, no setting |
| IX. Accessible | Pass by design (R4 to R8); verified by quickstart |
| X. Through the API | Not applicable: help copy, no record |
| XI. Short record | Pass. Spec 120, plan under 100, research under 80; no data model, no contracts (the component API is below) |

Re-checked after design: unchanged.

## Component contract: `x-help`

| Prop or slot | Required | Meaning |
| --- | --- | --- |
| `label` | yes | Accessible name of the mark, a whole translated sentence |
| `title` | yes | The question heading the bubble |
| `guide` | yes | Portal page id behind "Read the guide", optionally `#section`, the anchor of one of its `##` headings |
| `align` | no | `left` (default) or `right`, kept from today |
| default slot | yes | The explanation, at most three sentences |
| `example` slot | no | The "For example" block |

The "Read the guide" link carries `target="_blank"` and an accessible name ending in "(opens in a new tab)". It sits alone in the footer, aligned right.

## Call sites

| Screen | label | title | guide |
| --- | --- | --- | --- |
| Profile, Email address section | Help about the email address | What does this address do? | `account.profile#your-email-address` |
| API keys, sensitive fields checkbox | Help about sensitive fields | Why leave this off? | `security.api-keys#create-a-key` |

Profile explanation: "Sign in links, password resets and security warnings all go to this address. We ask for your password or a passkey before changing it, and the new address only starts working once you confirm it from its inbox." The API key bubble keeps today's two sentences.

## Project Structure

```text
specs/101-help-bubbles/      plan.md, research.md, quickstart.md, spec.md, checklists/
resources/views/components/help.blade.php          rewritten
resources/views/components/page-section.blade.php  + help slot after the <h2>
resources/views/app/settings/account/profile/index.blade.php      + bubble
resources/views/app/settings/account/security/_api-keys.blade.php migrated
app/ViewModels/Docs/PortalCatalogue.php            + pageForReader()
.claude/skills/help-bubbles/SKILL.md               new
.claude/rules/components.md                        + x-help row in the table
lang/*.json                                        via monica:localize
tests/Feature/Views/Components/HelpTest.php        new
tests/Unit/ViewModels/Docs/HelpGuideCoverageTest.php  new
tests/Unit/ViewModels/Docs/PortalCatalogueTest.php    + pageForReader cases
```

## The skill

`.claude/skills/help-bubbles/SKILL.md`, triggered when a form, a setting or a concept is added or changed. It says: what earns a bubble (a consequence the label cannot carry: an irreversible step, something sent elsewhere, a term of Monica's own) and what does not (a self explaining label, validation, a sentence the screen already prints); the writing rules (title as a question, at most three sentences, an example in the reader's own world, plain register of the translations skill); that `guide` is a portal page id, found with `grep -rh "^id:" docs/portal/en`; that a missing page is written in the same change with the `writer` skill; and where the mark goes (beside a section title through the `help` slot, beside a control otherwise, never inside a heading or a label).

## Tests

- `HelpTest`: the button's `aria-label`, `aria-expanded` and `aria-controls` bindings; the group's `aria-labelledby`; no `role="tooltip"`; the guide's URL with its section, `target="_blank"` and new tab wording; the example block present only when given; an unknown guide renders the bubble without the footer link.
- `HelpGuideCoverageTest`: every `<x-help` in `resources/views` names a `guide`, and every `guide` is an `en` page whose `#section`, when given, is one of its `##` anchors.
- `PortalCatalogueTest`: `pageForReader` returns the reader's locale when present, `en` otherwise, null for an unknown id.
- Shared component change: run the whole suite (`components.md`).

## Complexity Tracking

None.
