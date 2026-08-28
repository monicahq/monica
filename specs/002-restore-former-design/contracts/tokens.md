# Contract: The Token Layer

**Feature**: [../spec.md](../spec.md) | **Configuration file**: `resources/css/app.css`

This is the whole of what the configuration file may hold: the token names, the two palettes they
resolve to, the type steps and the keyframes. Everything else about the design is a utility class on
the markup (FR-030).

The values below are the former direction's, read from `git show 11f266e:resources/css/app.css`.
They are the target, not a suggestion.

## Colour tokens

Each name below is exposed to Tailwind as a `--color-*` alias resolving to a runtime variable, so
`bg-page`, `text-ink` and `border-hairline` work as utilities and flip with the theme. That
indirection is the existing mechanism and it does not change.

| Token | Light | Dark | What it is for |
|---|---|---|---|
| `page` | `#f7f7f6` | `#111113` | The window behind everything |
| `canvas` | `#ffffff` | `#1a1a1d` | A surface sitting on the page |
| `card` | `#fafafa` | `#202024` | A surface sitting on a canvas |
| `sunken` | `#fbfbfa` | `#161619` | A surface set into the page, the navigation column included |
| `input` | `#ffffff` | `#1a1a1d` | A control somebody types into |
| `hairline` | `#e7e7e4` | `#2b2b31` | The default border, which is what carries separation |
| `hairline-strong` | `#e2e2de` | `#33333a` | A border that has to be seen against a tint |
| `hairline-soft` | `#f1f1ee` | `#26262b` | A border between rows inside one container |
| `hover` | `#f4f4f1` | `#26262c` | The tint under a pointer |
| `focus` | `#b9b9b3` | `#55555f` | The visible focus ring |
| `ink` | `#1d1d20` | `#f0f0ee` | Primary text |
| `body` | `#74747a` | `#9d9da4` | Running text |
| `muted` | `#8b8b90` | `#8a8a91` | Secondary text |
| `muted-soft` | `#a0a0a4` | `#71717a` | Text that is barely there |
| `placeholder` | `#b4b4b8` | `#5c5c65` | Placeholder text |
| `primary` | `#1d1d20` | `#f0f0ee` | The filled button, which is ink and inverts |
| `on-primary` | `#ffffff` | `#111113` | Text on the filled button |
| `disabled` | `#e9e9e5` | `#26262c` | A control that cannot be used |
| `on-disabled` | `#a0a0a4` | `#6a6a73` | Text on it |
| `brand` | `#3f5fd0` | `#93a7f0` | The one accent |
| `success` | `#4f8a49` | `#4f8a49` | Fixed, reads the same in both themes |
| `warning` | `#9a6a24` | `#9a6a24` | Fixed |
| `error` | `#9a3f2b` | `#9a3f2b` | Fixed |

`accent` and `accent-foreground` alias `primary` and `on-primary`, as they do in both trees.

`color-scheme` is declared `light` on `:root` and `dark` on `.dark`, so a native control matches.

### Tokens that are removed

`sand`, `sand-deep`, `ink-soft`, `focus-ring`, `scrim`, `link`, `link-hover`, the five
`action-*` tokens, `error-hover`, `error-tint`, and every one of the twenty one tokens making up the
seven object hues (`terracotta`, `marigold`, `forest`, `indigo`, `plum`, `sky`, `rose` with their
`-deep` and `-tint` variants).

A token is removed only once nothing references it. The four view models named in
[research decision 8](../research.md) reference the hues from PHP, so they are edited in the same
commit that drops the tokens, or the build silently produces no class and the screen loses its
tint with nothing to say so.

### Colour that is content, not interface

These are hex strings in the database and are not tokens. They stay exactly as they are:
`vaults.colour`, `entity_types.colour`, `value_list_items.colour`, `contacts.avatar_colour` and
`entities.avatar_colour`. `App\Enums\VaultColourEnum` offers eight of them and is already the former
palette, so it is not edited.

## Type

One family for the interface, one for machine values, and no display family.

| Token | Value |
|---|---|
| `--font-sans` | `'Inter', ui-sans-serif, system-ui, sans-serif` plus the emoji stack, as in `11f266e` |
| `--font-mono` | `'IBM Plex Mono', ui-monospace, 'SF Mono', Menlo, Consolas, monospace` |
| `--font-display` | Removed |

The named steps `page-title`, `section-title`, `card-title`, `label`, `nav` and `code` are kept and
re-pointed to the former direction's flatter values. `display-1` and `display-2` are removed.
`base`, `sm` and `xs` keep Tailwind's names. Each step keeps carrying its own leading, weight and
tracking, so a heading stays one utility.

A page title is set in the interface family. If any screen still reads as though it wants a display
face, the answer is weight and space, not a second family.

## Radii

`--radius-md: 8px`, `--radius-lg: 11px`, `--radius-xl: 13px`, as in `11f266e`.

## Motion

The five toast keyframes and their five animation tokens are kept as they are in both trees, along
with the rule that the timer keyframe's 4500ms and the auto dismiss timeout in
`components/toaster.blade.php` are one number in two places and change together.

The `prefers-reduced-motion` block that stills all five stays, and any transition the restored shell
adds is added to it.

## What else the file may hold

The `dark` custom variant, the `[x-cloak]` utility, the `::selection` colours, and the two classes
behind the quote panel on the credentials screens (`.quote-panel` and `.quote-card`) with the
`--quote-bands` and `--quote-shadow` variables they read. All four are in both trees.

Nothing else. A rule that could be a utility on the markup is a utility on the markup.
