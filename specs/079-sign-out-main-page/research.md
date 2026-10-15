# Research: Sign out from the main page

## R1. Where the menu stands

- **Decision**: the last control of each top bar. Account layout: after `x-theme-toggle`.
  Marketplace: inside `@auth`, in place of the avatar. Admin: in place of the avatar.
- **Rationale**: the marketplace and admin bars already end on the avatar, so the vaults list takes
  the same place.
- **Account settings in the bar**: the account layout passes `:settings="false"` to `x-bar-links`,
  the rule the shell already follows when something else carries the settings. One control per
  thing in one bar.

## R2. Signing out is a form, not a link

- **Decision**: `<form method="post">` with `@csrf` to `auth.login.destroy`, exactly as
  `components/sidebar-foot.blade.php` does. `LoginController::destroy()` and the route are untouched.
- **Rationale**: a GET that signs somebody out can be triggered by any page they visit.

## R3. `x-actions-menu` with the avatar as its glyph

- **Decision**: `x-account-menu` wraps `x-actions-menu` (variant `ghost`, label "Account") and puts
  `x-avatar` in its `glyph` slot.
- **Rationale**: `x-actions-menu` is the component `components.md` extracted for exactly this
  contract: the menu button, `aria-expanded`, Escape and click outside. Three bars call it, so the
  wrapper is extracted too.
- **Alternatives**: the menu of `sidebar-foot`, rejected because it opens upwards and shows the name;
  a plain Sign out button, the first design, replaced on 2026-10-14 by the decision in the spec.

## R4. Icons

- **Decision**: `settings` and `key` at 16 pixels, the glyphs the sidebar menu uses for the same two
  entries.

## R5. Translations

- **Decision**: "Account", "Account settings" and "Sign out" are all present in the 31 locale files.
  No new string. `monica:localize --check` passes.

## R6. An expired session

- **Finding**: a POST with a stale token answers the framework's 419 page, as the existing sidebar
  control does. Out of scope.

## R7. What each test proves

| File | Proves |
| --- | --- |
| `AccountMenuTest` | The avatar opens a menu named "Account"; the menu leads to `settings.index`; Sign out is a submit `menuitem` posting to `auth.login.destroy` with a token |
| `TopBarTest` | `x-account-layout` shows the initials and the menu, never the email address; `x-shell` has no Sign out (FR-004) |
| `MarketplaceLayoutTest` | Signed in renders the menu; a visitor does not (FR-009) |
| `AdminLayoutTest` | The admin bar renders the menu (FR-010) |

The POST itself is proven by `LoginControllerTest::it_signs_a_user_out`, and the open and close
behaviour by `ActionsMenuTest`.
