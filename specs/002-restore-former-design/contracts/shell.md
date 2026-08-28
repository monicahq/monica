# Contract: The Shells and the Navigation

**Feature**: [../spec.md](../spec.md)

Five shells. Which one a screen uses does not change; what each one draws does.

## `<x-shell>` (`app/View/Components/Shell.php`, `layouts/shell.blade.php`)

The shell of every signed in screen that has somewhere to navigate. Thirty screens use it.

### Slots and props, after the restoration

| Name | Kind | Required | What it is |
|---|---|---|---|
| `title` | prop | no | The document title |
| `wide` | prop | no | Swap the reading column for the content column |
| `sidebar` | slot | yes | The navigation column. The screen decides which one and what is current |
| `breadcrumb` | slot | no | Where you are, in the bar |
| `actions` | slot | no | What you can do from the bar. Without it, the bar says who is signed in |
| `footer` | slot | no | A bar at the foot of the window, for a screen whose actions must stay reachable through a long form |

`dock` and `barEnd` are removed. `bar`, which thirty screens pass today, becomes `breadcrumb`, which
is what it holds.

### What the shell draws

- A grid, one column below `lg` and `264px` plus the rest above it. The sidebar slot is the first
  child of that grid.
- The backdrop for the drawer, shown below `lg` only.
- A sticky bar, `h-13`, hairline underneath, page colour at 90% with a blur behind it. It carries the
  menu button below `lg`, then the breadcrumb, then the actions or the signed in email address.
- The content, capped at `max-w-240` with `px-4 sm:px-7` unless `wide`, in which case it fills the
  window and becomes exactly as tall as it, so a region inside it can own the scrolling.
- The footer bar when the slot is filled: sticky, hairline above, canvas coloured, its contents
  lined up with the content column while the rule and the background run the full width.

### The drawer state

`x-data="{ navOpen: false }"` is declared on the grid, which is a child of `<body>`. It must not
move above the body: Alpine is re-initialised from the body after a Turbo navigation, and state
declared above it is never walked again. Escape closes it. An effect toggles `overflow-hidden` on
the body while it is open.

This is the former tree's arrangement and its reason, and both are recorded in the comment at the
top of `11f266e:resources/views/layouts/app.blade.php`. Keep the comment.

## `<x-account-layout>`

The shell for a screen that stands outside every vault and has nothing to navigate: the vault form
and the getting started screen. A bar with the mark, the theme switch, the way to the settings and
the avatar, then an optional breadcrumb strip, then the content. No navigation column.

Unchanged in structure. Restyled to the restored tokens.

## `<x-guest-layout>`

Eleven screens: the eight credentials screens, and the three a private link opens
(`shared/contact`, `shared/password`, `shared/gone`). The form on one side, the quote panel on the
other, and below the small breakpoint the panel gives way to the form rather than pushing it off the
screen.

No navigation, no way into the product, and nothing that identifies the account (FR-015).

## `<x-admin-layout>`

Seven screens. Keeps a shell of its own so an administrator always knows which area they are in.
Restyled to the restored tokens, and it must not become the sidebar shell.

## `<x-marketplace-layout>`

One screen today. Keeps a shell of its own because it is read by somebody who may be signed in to
nothing.

## The navigation column

Two components, both filling the `sidebar` slot, both `w-72` fixed drawers below `lg` and sticky
full height columns above it, both on the sunken surface with a hairline down the right.

### `<x-vault.sidebar>`

| Prop | What it is |
|---|---|
| `vault` | The vault the screen is about |
| `current` | Which destination is current, defaulting to `dashboard` |
| `taskCount` | A count beside the tasks entry, when there is one |

The vault switcher sits above the navigation, because it is the only way across the boundary and is
not a destination inside the vault. The vault being looked at is marked, not offered as a link to
where you already are. The row at the foot is who is signed in and is the way into their own
settings, and the whole row is the link.

The component asks the account for its vaults itself, the way the dock does today
([research decision 4](../research.md)). It is not handed them.

Entries pointing at screens that do not exist yet (journal, tasks, reminders, files) stay drawn and
not offered.

### `<x-settings.sidebar>`

| Prop | What it is |
|---|---|
| `current` | Which settings destination is current, defaulting to `profile` |

The way out of the settings sits above the navigation, the same way the vault switcher does. The
current entry is marked by weight as well as by its surface, and hovering takes the surface without
the weight, so words do not shift sideways under the pointer.

`id="settings-nav"` is kept: it is the target of an Ajax form on the profile screen, which is also
why there is one aside that becomes a drawer rather than two copies of it.

The closed state is a plain class rather than an Alpine binding, so the drawer cannot flash open
before Alpine starts.

### Announcing the current position

`aria-current` on the current entry, on both components (FR-017). Weight and surface say it to
somebody looking; `aria-current` says it to somebody listening. Neither on its own is enough.

## What is removed

| Component | Why |
|---|---|
| `components/dock.blade.php` | Replaced by the two sidebars |
| `components/launcher/index.blade.php` | Its destinations are in the sidebars |
| `components/launcher/tile.blade.php` | Only the launcher used it |
| `components/launcher/tile-mark.blade.php` | Only the tile used it |
| `components/vault/bar.blade.php` | The shell's bar and breadcrumb carry this |

Every destination any of them offered must be reachable from a sidebar or the bar before the
component is deleted. The check is mechanical: list the anchors in each, and find each one in the
restored navigation.

`components/bar-links.blade.php` is undecided and deliberately so. The documentation, the
marketplace and the administrator's one way in all survive; whether they stay in the bar or move
into the column is settled by the shell commit. If they stay, the component and its test are
restyled and kept.

## Screen by screen mapping

Every screen below passes `bar` today and passes `breadcrumb` afterwards. The `dock` column says
whether it passes a sidebar afterwards, and which.

| Screen | Today | Afterwards |
|---|---|---|
| `settings/index` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/profile/index` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/preferences/index` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/security/index` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/security/two-factor` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/logs/index` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/logs/emails` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/default-values/index` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/entity-types/index` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/entity-types/form` | `bar`, `dock` | `breadcrumb`, settings sidebar, `footer` |
| `settings/account/templates/index` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/templates/form` | `bar`, `dock` | `breadcrumb`, settings sidebar, `footer` |
| `settings/account/templates/builder` | `bar`, `dock` | `breadcrumb`, settings sidebar, `wide` |
| `settings/account/templates/field-type` | `bar`, `dock` | `breadcrumb`, settings sidebar, `wide` |
| `settings/account/templates/import` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/templates/export` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/templates/share` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/templates/section-export` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `settings/account/templates/section-share` | `bar`, `dock` | `breadcrumb`, settings sidebar |
| `vaults/index` | `bar`, `barEnd` | `breadcrumb`, no sidebar, and it moves to `x-account-layout` |
| `vaults/show` | `bar`, `dock` | `breadcrumb`, vault sidebar |
| `vaults/settings` | `bar`, `dock` | `breadcrumb`, vault sidebar, `footer` |
| `vaults/contacts/index` | `bar`, `dock` | `breadcrumb`, vault sidebar |
| `vaults/contacts/form` | `bar`, `dock` | `breadcrumb`, vault sidebar |
| `vaults/contacts/show` | `bar`, `dock` | `breadcrumb`, vault sidebar |
| `vaults/contacts/edit` | `bar`, `dock` | `breadcrumb`, vault sidebar, `footer` |
| `vaults/contacts/set-aside` | `bar` | `breadcrumb`, vault sidebar |
| `vaults/contacts/share-links/index` | `bar` | `breadcrumb`, vault sidebar |
| `vaults/contacts/share-links/form` | `bar` | `breadcrumb`, vault sidebar |
| `vaults/contacts/share-links/show` | `bar` | `breadcrumb`, vault sidebar |

Two notes on that table.

`vaults/index` is the one screen on `x-shell` with no dock, because the dock points into one vault
and that screen is where you choose between them. The same reasoning makes it an account layout
screen once the shell requires a sidebar, which is where the vault form and the getting started
screen already are.

Four screens pass no dock today (`set-aside` and the three share link screens) and gain a vault
sidebar, because they are inside a vault and there is now a column that says so.

The other slot names appearing in these files (`icon`, `trigger`, `action`, `help`, `end`, `footer`
on a panel) belong to the panel, dropdown and modal components and are untouched by this contract.
