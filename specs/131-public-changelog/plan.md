# Implementation Plan: What’s new for signed out visitors, and its missing logo

**Branch**: `2026-11-18-public-changelog` | **Date**: 2026-11-18 | **Spec**: [spec.md](spec.md)

## Summary

Build What’s new on the frame that carries the logo, then open its one reading route to visitors
and leave every vote, comment, link and seen mark to signed in people. Decisions are in
[research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current) | **Frontend**: Blade, Tailwind, Alpine Ajax

**Storage**: none new. Entries are Markdown files; votes, comments and seen marks are existing rows

**Testing**: PHPUnit, feature and unit | **Scope**: one screen, one route, one shared bar component

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. No new action; `MarkChangelogSeen` is skipped for a visitor |
| II. Simplicity | Pass. One nullable user, no new class |
| III. Tested | Pass. Controller, view model and route protection tests below |
| V. Localized | Pass. "Sign in" already translated; no new string |
| VI. Nothing changes under anybody | Pass. Signed in behaviour unchanged except the logo |
| IX. Accessible | Pass. Same markup; the sign in link is a plain link with visible text |
| X. API | Pass. See research R4 |
| XI. Short record | Pass. No data model, contracts or quickstart: nothing new to describe |

## Project Structure

```text
routes/web.php                                         move GET changelog out of the auth group
app/Http/Controllers/App/ChangelogController.php       nullable user, no seen mark for a visitor
app/ViewModels/ChangelogViewModel.php                  ?User; no votes, counts, link for a visitor
resources/views/app/changelog/index.blade.php          x-account-layout; pass $feedback to partials
resources/views/app/changelog/_entry.blade.php         include _feedback only when $feedback
resources/views/app/changelog/_fixes.blade.php         same
resources/views/components/bar-links.blade.php         "Sign in" for a guest
changelog/                                             one What’s new entry (changelog skill)
tests/Feature/RouteProtectionTest.php                  changelog.index is public
tests/Feature/Controllers/App/ChangelogControllerTest  visitor sees the page, no feedback, no seen row
tests/Unit/ViewModels/ChangelogViewModelTest           visitor: no vote, no comment count, no link
```

## Post-design check

Unchanged: every row above passes.
