# Implementation Plan: A Page of Our Own When an Address Leads Nowhere

**Branch**: `2026-10-12-not-found-page` | **Date**: 2026-10-12 | **Spec**: [spec.md](spec.md)

## Summary

Every HTML not found answer renders `errors/404.blade.php`. The page is drawn from the mock, inside
the guest shell. An unmatched address is first routed through a fallback route, so that it gets the
`web` group, and with it the reader's language (research R2). JSON, API and CardDAV answers keep
their status and their current rendering (R1, R5).

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13.35

**Primary Dependencies**: Blade, Tailwind CSS. No new package.

**Storage**: none. One static image, `public/images/not-found.webp`.

**Testing**: PHPUnit feature tests.

**Target Platform**: the web application, desktop to phone, light and dark themes.

**Project Type**: web application (Laravel monolith).

**Performance Goals**: no query on the page. The illustration declares its size, so nothing shifts while it loads.

**Constraints**: WCAG 2.2 AA; every configured locale; no change to JSON, API or CardDAV statuses.

**Scale/Scope**: one view, one controller with one method, one route, about seven strings.

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | No action, since nothing is done. No view model either (R3), a stated deviation from the views skill. |
| II. Simplicity | One Blade file, plus a two line controller. |
| III. Tested | `NotFoundControllerTest` covers the four scenarios in the quickstart. `RouteProtectionTest` lists `notFound.show` as public. |
| IV. Laravel conventions | `errors/404` and `Route::fallback` are the documented mechanisms. |
| V. Localized | Every string goes through `__()`; `monica:localize` is run. The fallback route sets the locale (R2). |
| VI to VIII | Not touched. |
| IX. Accessible | One `h1`; decorative image with `alt=""`; `focus-ring` on the links and the button; no motion. |
| X. API | No capability is added. The API keeps its JSON 404. |
| XI. Short record | No `data-model.md` and no `contracts/`: the feature has no data and no interface. |
| Self hosted equals hosted | Same page on both. |

Gate: passes. It passes again after design, with no new violation.

## Project Structure

```text
app/Http/Controllers/App/NotFoundController.php                   new: show() aborts with 404
routes/web.php                                                    Route::fallback outside api/* and mcp, named notFound.show
resources/views/errors/404.blade.php                              new: the page
public/images/not-found.webp, not-found-dark.webp                 new: the day and night illustrations
lang/*.json                                                       through monica:localize
tests/Feature/Controllers/App/NotFoundControllerTest.php          new
tests/Feature/RouteProtectionTest.php                             notFound.show added to $publicRoutes
```

## Page outline

Inside `<x-guest-layout :title="__('Page not found')">`, from top to bottom:

1. A header: `x-logo` and "Monica", as one link to `route('home.index')` that names itself by its text.
2. A `<main>` holding:
   - the illustration (`alt=""`, `width="1335"`, `height="532"`, scaling to its column);
   - the `h1` "We’re sorry, but the page you’re looking for doesn’t exist.";
   - the line "It may have moved, or the link might be broken.";
   - `x-button` as a link reading "Return home".
3. A `<footer>`: "Monica © {year} · Open source personal CRM" on one side, and "Docs" (`route('docs.index')`) and "Legal" (`config('monica.terms_url')`) on the other. It stacks under `sm`.

Check how `x-button` renders as a link before writing it.
`components/auth-page.blade.php` already holds a footer like this one. It is not extracted, because
two files do not reach the three file bar in `components.md`.

## Complexity Tracking

| Deviation | Why | Simpler alternative rejected |
| --- | --- | --- |
| A view without a view model | No controller renders an error view | A render callback building one, which repeats the JSON rule (R1) |
| A GET on a POST only web address answers 404, not 405 | The fallback route matches every GET outside `api/*` and `mcp` | No fallback, which shows the page in the default language to everybody (R2) |
