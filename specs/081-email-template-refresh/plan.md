# Implementation Plan: Refreshed Layout for Every Email Monica Sends

**Branch**: `2026-10-17-email-template-refresh` | **Date**: 2026-10-17 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/081-email-template-refresh/spec.md`

## Summary

Replace Laravel's stock mail theme with the design's layout by publishing the html mail components
and the theme stylesheet into `resources/views/vendor/mail/html`. Light colours are inlined from the
theme; dark colours live in a `<style>` block under `prefers-color-scheme` and Outlook's attributes.
The mark ships as two committed PNG files. No notification and no PHP class changes
([research](research.md), [contract](contracts/email-layout.md)).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major), Blade

**Primary Dependencies**: Laravel's Markdown mail renderer and `tijsverkoyen/css-to-inline-styles` 2.4, both already installed

**Storage**: N/A. Two image files in `public/images/email/`

**Testing**: PHPUnit, one feature test of the rendered layout

**Target Platform**: email clients: Apple Mail, Gmail, Outlook (web and Windows)

**Project Type**: web application, server rendered

**Performance Goals**: N/A; the message grows by a style block and two image references

**Constraints**: no SVG, no web font, no remote host, no new string, the plain text part untouched

**Scale/Scope**: 21 notifications, 7 published views, 1 stylesheet, 2 images, 1 test

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status |
| --- | --- |
| I. Actions | Pass. Presentation only, no operation added |
| II. Simplicity | Pass. Published framework views, no new class |
| III. Tested | Pass. A layout test (R7); the notification tests keep proving each email's copy |
| IV. Laravel conventions | Pass. Publishing the mail components and theme is the documented route |
| V. Localized | Pass. No new string (FR-007); `lang` from the locale |
| VI to VIII | Not touched |
| IX. Accessible | Pass. Contrast measured (R6), alternative texts, presentation tables, language (FR-015, FR-016) |
| X. API | N/A. No capability added |
| XI. Short record | Pass. spec 120, plan under 100, research under 80; `data-model.md` omitted, nothing to model |
| Repository layout | Pass. Only `resources/views/vendor`, `public/images`, `tests/Feature/Views/Layouts` |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/081-email-template-refresh/
├── plan.md
├── research.md
├── quickstart.md
├── contracts/email-layout.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
resources/views/vendor/mail/html/
├── layout.blade.php         # new: page, card, head, dark stylesheet
├── header.blade.php         # new: mark and name bar
├── footer.blade.php         # new: small mark and copyright
├── button.blade.php         # new: neutral, success, error
├── panel.blade.php          # new: sunken block
├── subcopy.blade.php        # new: fallback strip
├── message.blade.php        # changed: passes the regions to the new layout
└── themes/default.css       # new: light palette, inlined
resources/views/vendor/notifications/email.blade.php   # changed only if the heading or strip markup needs it
public/images/email/mark-light.png, mark-dark.png       # new, rendered from public/images/logo.svg
tests/Feature/Views/Layouts/MailLayoutTest.php          # new
```

**Structure Decision**: everything stays in the folders the constitution already names; the email
templates live under `resources/views/vendor` by its own description.

## Risks

- Each published Blade view needs the opening comment line the code style asks of every template.
- The inliner rewrites the HTML it is given. The dark `<style>` block surviving it is the premise of
  R2. The layout test asserts the `light dark` declaration in the rendered message, and the
  quickstart's browser check proves the dark rules apply.
- Gmail clips messages over 102 KB. The style block is a few kilobytes; the digest is the longest
  email and is checked once in the quickstart.
