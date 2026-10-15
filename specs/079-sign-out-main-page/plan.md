# Implementation Plan: Sign out from the main page

**Branch**: `2026-10-13-sign-out-main-page` | **Date**: 2026-10-14 | **Spec**: [spec.md](spec.md)

## Summary

One new Blade component, `x-account-menu`, puts the avatar of whoever is signed in at the end of
three top bars: the account layout (the vaults list and the vault form), the marketplace layout for a
signed in person, and the admin layout. The avatar opens a menu holding Account settings and Sign
out. The component is `x-actions-menu` with the avatar as its glyph (research R3). Signing out itself
does not change (R2).

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js. No new package.

**Storage**: none.

**Testing**: PHPUnit feature tests, rendered with `$this->blade()`.

**Target Platform**: the web application, desktop to phone, light and dark themes, the inverted
admin bar.

**Project Type**: web application (Laravel monolith).

**Performance Goals**: no query beyond the signed in user the layouts already load.

**Constraints**: WCAG 2.2 AA; every configured locale; no email address in the bar.

**Scale/Scope**: one component, three layouts touched, zero new strings (R5), four test files.

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | No action and no view model: the menu links to a screen and posts to a route that exist. |
| II. Simplicity | One component of about thirty lines, built on `x-actions-menu`. |
| III. Tested | `AccountMenuTest` proves the component; the three layout tests prove where it stands and where it does not (R7). |
| IV. Laravel conventions | A POST form with `@csrf`, the documented way to sign out. |
| V. Localized | "Account", "Account settings" and "Sign out" are already in all 31 locales. |
| VI to VIII | Not touched. |
| IX. Accessible | The menu contract of `x-actions-menu` (menu button, `aria-expanded`, Escape, click outside); real `menuitem` entries. |
| X. API | The API already has `DELETE /logout` (`routes/api.php`). Nothing to add. |
| XI. Short record | No `data-model.md`, no `contracts/`. `spec.md` is over its cap of 120 lines; the pull request says so. |
| Self hosted equals hosted | Same bars on both. |

Gate: passes. It passes again after design, with no new violation.

## Project Structure

```text
resources/views/components/account-menu.blade.php             new: the avatar, the menu, its two entries (R3)
resources/views/layouts/account.blade.php                     x-account-menu after x-theme-toggle; x-bar-links :settings="false"
resources/views/layouts/marketplace.blade.php                 x-account-menu replaces x-avatar inside @auth
resources/views/layouts/admin.blade.php                       x-account-menu replaces x-avatar
tests/Feature/Views/Components/AccountMenuTest.php            new
tests/Feature/Views/Layouts/TopBarTest.php                    the account bar shows the avatar and the menu, the shell does not
tests/Feature/Views/Layouts/MarketplaceLayoutTest.php         signed in sees the menu, a visitor does not
tests/Feature/Views/Layouts/AdminLayoutTest.php               the admin bar carries the menu
```

## Component outline

`x-account-menu` takes `user`. It renders `<x-actions-menu variant="ghost" :label="__('Account')">`
with `<x-avatar :user="$user" :size="26" />` in the `glyph` slot, then:

1. `x-actions-menu.item` to `route('settings.index')`, with the `settings` icon: "Account settings".
2. A `<form>` posting to `route('auth.login.destroy')` with `@csrf`, holding a
   `<button type="submit" role="menuitem">` with the `key` icon: "Sign out".

## Complexity Tracking

No deviation.
