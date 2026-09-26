# Implementation Plan: Documentation portal

**Branch**: `2026-09-25-docs-portal` | **Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

A public `/docs` page drawn from the design, fed by one view model listing the four documentation
areas (R2), with a card linking only where pages exist (R5). A public `/llms.txt` lists the Markdown
copies of the API reference in English (R3, [contract](contracts/llms-txt.md)). No schema change, so
no `data-model.md`; the tests table below is the validation guide, so no `quickstart.md`.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)
**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js (theme toggle only)
**Storage**: none
**Testing**: PHPUnit: one view model test, two controller tests, `RouteProtectionTest`
**Target Platform**: the public site, signed in or not
**Project Type**: server rendered web application
**Performance Goals**: no database query on either route
**Constraints**: WCAG 2.2 AA; portal strings in every locale; no link to `#` or to a missing page
**Scale/Scope**: 2 controllers, 1 view model, 1 view, 2 routes, about 20 strings

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.* Re-checked after Phase 1: unchanged.

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | No business logic: a read-only page and a text file |
| II. Simplicity over abstraction | Pass | One view model, no new component (R4) |
| III. Tested before merged | Pass | Tests table below |
| IV. Laravel conventions | Pass | One named route per line, class array notation |
| V. Localized and safe | Pass | Portal strings through `monica:localize`; `/llms.txt` English, as the reference it lists (R3) |
| VI. Nothing changes under anybody’s feet | Pass | New routes only; `/docs/api` untouched |
| VII. Stable core | N/A | No structure touched |
| VIII. Nothing disappears silently | Pass | Nothing removed |
| IX. Accessible to everybody | Pass | One `h1`, a list of cards, illustration hidden, focus rings from `focus-ring` |
| X. Everything goes through the API | N/A | Documentation, no capability added |
| XI. The written record is short | Pass | spec 102, plan and research under their caps |

## Project Structure

```text
routes/web.php                                          # docs.index, llmsText.index (R1)
app/Http/Controllers/App/Docs/DocsController.php        # new
app/Http/Controllers/App/Docs/LlmsTextController.php    # new
app/ViewModels/Docs/DocsViewModel.php                   # new: areas(), host(), llmsText() (R2)
resources/views/app/docs/index.blade.php                # new (R4, R5)
lang/*.json                                             # through monica:localize
tests/Unit/ViewModels/Docs/DocsViewModelTest.php        # new
tests/Feature/Controllers/App/Docs/DocsControllerTest.php      # new
tests/Feature/Controllers/App/Docs/LlmsTextControllerTest.php  # new
tests/Feature/RouteProtectionTest.php                   # two public routes
```

**Structure Decision**: follows the existing `Docs` domain: controllers, view models and views sit
next to the API reference's.

## Tests

| Test | Proves |
| --- | --- |
| `DocsViewModelTest` | Only the API area has a URL; the others are null |
| `DocsViewModelTest` | `llmsText()` links the whole reference and every section's Markdown copy, in English under the French locale |
| `DocsControllerTest` | A signed out visitor gets 200 with "Documentation" and a link to `/docs/api` |
| `DocsControllerTest` | A signed in visitor is not offered Sign in |
| `LlmsTextControllerTest` | 200, `text/plain`, starts with `# Monica` |
| `RouteProtectionTest` | `docs.index` and `llmsText.index` are listed as public |

## Complexity Tracking

None.
