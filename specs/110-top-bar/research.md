# Research: One top bar, outside and inside a vault

Design source: Claude Design project `b78dc791-8645-424e-b24c-635ab7aea4f3`, `Header.dc.html`, option
1a, with the design system's `components.css` (`mn-btn`, `mn-btn-small`, `mn-btn-ghost`,
`mn-btn-secondary`).

## R1. One component draws the whole right side

- Decision: `x-bar-links` grows to hold the whole cluster in the order of FR-001: Docs, Marketplace,
  Admin space, the divider, `x-notification-bar`, `x-theme-toggle`, `x-account-menu`. The account
  layout and the shell each render it once and nothing else on the right.
- Rationale: it is already the one thing both bars share, and only those two bars use it, so FR-016
  holds by construction. One component is what makes SC-001 and SC-002 true rather than hoped for.
- Alternatives: a new `x-top-bar` component (a second name for what `x-bar-links` already is);
  copying the cluster into both layouts (two copies of an ARIA contract, which the components rule
  forbids at the second file).

## R2. The links are buttons from the button component

- Decision: Docs and Marketplace become `x-button.ghost size="small"`, Admin space
  `x-button.secondary size="small"`, each with the glyph in the `icon` slot and the label in a span
  shown from the `topbar-labels` breakpoint, as today.
- Rationale: `size="small"` is already `h-8 px-3 text-sm`, which is `mn-btn-small` exactly (FR-007).
  The secondary variant in the application uses `border-hairline-strong` and `shadow-control`
  where the design writes `--hairline`; the application's button wins, as the precedence order says.
- Alternatives: keeping the hand written link classes (they are `h-9`, and a fourth button recipe).

## R3. Glyphs

| Place | Glyph name | Change |
| --- | --- | --- |
| Docs | `journal` | Path redrawn to the open book of 1a. Used only for the docs, in the bar and the docs portal |
| Notifications | `bell` | Path redrawn to the 1a bell. Used only for notifications, in the bar and the settings column |
| Marketplace | `shop` | New. `gift` stays for what is published to the marketplace |
| Admin space | `shield` | New. `lock` stays, it means something kept private everywhere else |

Every glyph is drawn at 16 pixels with a 1.4 stroke in the bar.

## R4. Notification count

- Decision: the count sits on a 32 pixel square ghost button, `bg-primary text-on-primary` with a
  two pixel `ring-page` around it so it reads against any bar colour. "9+" and the spoken label stay.
- Rationale: design 1a; the colour carries no meaning on its own, the label does (Principle IX).

## R5. The account trigger

- Decision: `x-account-menu` shows the avatar at 28 pixels and a 14 pixel `chevron-down` in
  `text-muted`, and is named "Your account". Its entries do not change.
- Consequence: the marketplace and admin bars, which also render `x-account-menu`, get the same
  trigger. This touches the control, not their bar, and keeps one account trigger in the product.
- Alternatives: a prop switching the chevron on (two looks for one control, for no reader's benefit).

## R6. The navigation columns

- Decision: the theme switch leaves the head of `x-vault.sidebar` and `x-settings.sidebar`, and
  `x-sidebar-foot` leaves both. With no caller left, `x-sidebar-foot` is deleted in this change.
- The head keeps the product link and, below `lg`, the close button. The close button moves to the
  right edge of the head with `ml-auto`, which the theme switch used to hold.

## R7. Narrow screens

- Below `topbar-labels` every link is a 32 pixel icon. At 375 pixels inside a vault the bar holds the
  menu button, the breadcrumb, the search link, the screen's actions and seven 32 pixel controls. The
  breadcrumb is the element that gives way (`min-w-0 truncate`); the cluster is `shrink-0`.
- Risk: a screen whose `actions` slot holds several buttons may overflow at 375 pixels. Checked per
  screen in the quickstart; a screen that overflows moves its actions into its page header.
