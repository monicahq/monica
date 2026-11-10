# Research: Admin space redesign

Source: Claude Design project `b78dc791-8645-424e-b24c-635ab7aea4f3`, `Admin Overview.dc.html`.

## R1. A bar that stays dark in both themes

- **Decision**: add fixed tokens to `resources/css/app.css`, declared once and never redefined under
  `.dark`: `--admin-bar` (`#18181b`), `--on-admin-bar` (`#fafafa`), `--on-admin-bar-muted`
  (`#a1a1aa`), `--admin-signal` (`#fbbf24`), each exposed as a `--color-*` utility.
- **Rationale**: the bar uses `bg-primary` today, and `--primary` flips to `#fafafa` in dark mode, so
  the bar turns light. The design keeps it `#18181b`. No Blade file uses Tailwind's raw palette
  (`zinc-*`, `amber-*`), and this keeps it that way.
- **Alternatives**: pinning `--primary` on the header with an arbitrary property (hidden indirection);
  raw `bg-zinc-900` (first raw palette use in the codebase).

## R2. Controls that sit on the bar

- **Decision**: two named recipes, `focus-ring-on-admin-bar` and its `-inset` form, since
  `focus-ring` is near black and measured 1.06:1 on the bar. `x-theme-toggle tone="inverted"` (the
  layout is its one caller) and a new `inverted` variant of `x-button`, reached through
  `x-account-menu variant`, use them. `focus-ring` moves from the button's `$base` into each variant.
- **Alternatives**: overriding `--focus-ring` on the header (the account menu panel opens inside it).

## R3. Striped band and hatched segment

- **Decision**: a Tailwind arbitrary background on an `aria-hidden` element:
  `repeating-linear-gradient(-45deg, var(--admin-signal) 0 10px, var(--admin-bar) 10px 20px)`, 8px
  high. The "On its way" segment and its legend swatch use the same technique with `--muted-soft`
  and `--hairline-strong`. Both are static, so reduced motion needs nothing. `print:hidden` on the band.
- **Design system departure**: patterns and accent colours, intended inside the admin space only.

## R4. Section list

- **Decision**: today's `@foreach` in the layout, plus `icon` (hidden below `sm`) and `section`
  (`admin.accounts.*`) keys. Index route: `aria-current="page"`; a page inside the section:
  `"true"`. Both get a 3px `--admin-signal` underline and full weight text, not colour alone.
  `<nav aria-label="{{ __('Admin sections') }}">`; `scroll-pt-32` keeps focus clear of the sticky bar.
- **Alternatives**: a component (one caller, below the three file bar in `components.md`).

## R5. The warning strip

- **Decision**: an `<aside>` labelled by its first sentence, under the header, `bg-warning-tint`,
  `border-warning-edge`, a `warning` glyph (added to `x-icon`, path taken from the design) in an
  `--admin-signal` tile. The sentence leaves the bar. The badge drops the design's cone glyph: the
  word "Instance" carries it, and one glyph for one badge is not worth a name in the icon set.
- **Alternatives**: `x-notice tone="warning"` (a rounded box inside the column, not a full width band).

## R6. "Counted at" and "Count again"

- **Decision**: `OverviewViewModel` takes the signed in `User`. `countedAtMoment()` is one
  `CarbonImmutable::now($user->timezone)`; `countedAt()` formats it with `$user->time_format`, and
  `x-timestamp` gets the same moment. "Count again" is
  `x-button.secondary size="small"` linking to `admin.overview.index`.
- **Alternatives**: a POST or an Alpine Ajax refresh (nothing is written, a GET is the honest verb).

## R7. Wordings that hold a number

| Where | Wording | Asked with |
| --- | --- | --- |
| Moderation, zero | `Nothing in the moderation queue right now.` | `__()`, zero compared in the view model |
| Moderation, more | `:count marketplace submission is waiting for a decision.\|:count marketplace submissions are waiting for a decision.` (existing key) | `trans_choice()` |
| Signed in | `signed in, last :count day\|signed in, last :count days` on `SIGNED_IN_DAYS` | `trans_choice()` |
| New accounts | `signed up, last :count day\|signed up, last :count days` on `NEW_ACCOUNT_DAYS` | `trans_choice()` |
| Bar alternative | `:count email sent: :delivered delivered, ...\|:count emails sent: ...`, or `No email sent yet.` at zero | `trans_choice()`, zero compared first |

- **Deviation from the design**: it reads "new account, last 30 days" under the figure 1. That label
  agrees with the figure and with the days at once, which one `trans_choice()` cannot do. "signed up"
  does not inflect on the figure in English, the same reasoning the "signed in" label already uses.

## R8. Proportions of the email bar

- **Decision**: `OverviewViewModel::emailShares()` returns each status as a percentage of
  `emailsSentCount()`, all zero when nothing was sent. The view sets each segment's width with an
  inline `style`, the one value Tailwind cannot know at build time.

## R9. Totals strip columns

- **Decision**: `grid-cols-2 sm:grid-cols-3 lg:grid-cols-6` with `gap-px` over a `bg-hairline` grid,
  so the cells read as one ruled strip. The design measures the container, breakpoints match it here
  because the column is capped at `max-w-280` on every admin screen.
