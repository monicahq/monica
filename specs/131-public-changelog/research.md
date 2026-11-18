# Research: What’s new for signed out visitors

## R1. Why the logo is missing

`resources/views/app/changelog/index.blade.php` is the only screen outside a vault built on `x-shell`.
`x-shell` draws `x-product-link` only through its `sidebar` slot (the vault sidebar, `x-nav-drawer`),
and What’s new passes none. `x-account-layout`, used by the vault list, draws it in its own bar.

- **Decision**: build What’s new on `x-account-layout`. Its `breadcrumb` slot and `max-w-240` body
  match what the screen already uses.
- Rejected: adding the logo to `x-shell`’s bar when no sidebar is given (changes a shared frame for one
  screen).

## R2. Where the public route lives

- **Decision**: move only `GET changelog` out of the `auth` group, with the middlewares `changelog`,
  `active` and `searchable`. `active` lets a visitor through and signs a deactivated person out, as it
  does everywhere. Votes and comments stay in the `auth` group.
- Rejected: `verified` on the public route (it redirects a visitor); a second public address (two
  links to share, two screens to keep in step).

## R3. What a visitor is told and shown

- **Decision**: the view model takes `?User`. With no user it reads no vote and no comment count,
  hides the link, and the view leaves out `_feedback` and the "Seen" divider. The controller marks
  nothing as seen.
- **Decision**: `x-bar-links` gains a "Sign in" button for a guest, as `x-docs.header` does. Its other
  entries already answer for nobody (no changelog link, no notification bar, no account menu, no
  admin button).

## R4. The API (principle X)

Reading the changelog through the API already exists with a token. A visitor reads a page, not an API
capability, so the API does not change and no endpoint becomes public.
