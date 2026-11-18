# Research: Admin space sidebar

## R1. Reuse `x-nav-drawer` rather than write an admin sidebar

- **Decision**: the admin layout renders `x-nav-drawer`, extended in three ways (R2).
- **Rationale**: `x-nav-drawer` already is the drawer the settings and support screens use: the column, the narrow-window slide in, the close button, the counts with their screen reader wording. `.claude/rules/components.md` extracts markup carrying an ARIA contract at its second file, so a hand-written admin drawer would be the defect that rule exists to stop.
- **Rejected**: a sidebar written in `layouts/admin.blade.php` (second copy of the drawer contract); moving the admin space onto `x-shell` (loses the dark admin bar across the full width, which the design keeps).

## R2. What `x-nav-drawer` gains

| Change | Why |
| --- | --- |
| `leaveUrl` and `leaveLabel` become optional; without them the drawer draws neither the product link nor the leave link, and its top row (the close button) shows only below `lg` | The admin bar already holds the logo and "Leave the admin space", and the product link leads into the vaults, which `AdminLayoutTest::it_offers_no_way_into_a_vault` forbids |
| A group `title` becomes optional | The admin sections are one flat list, as in the design |
| `current` may name a section a screen sits inside: an entry can carry `section: true`, which marks it `aria-current="true"` instead of `"page"` | The admin row distinguishes the exact screen from a screen inside a section today, and its test asserts it |

Settings and support pass what they pass today and render the same markup.

## R3. Layout below and above `lg`

- **Decision**: `lg` (1024px), the width `x-shell` folds its sidebar at. Above it the dark bar sits at the top of the page and scrolls away with it (not sticky, decided 2026-11-17); below it, a grid `248px | content` whose sidebar column is `sticky top-0 h-dvh`, so the sidebar stays in view (FR-005). Below `lg` the drawer lies over everything with the shell's scrim, and the page is locked by toggling `max-lg:overflow-hidden` on `body`.
- **Rationale**: one breakpoint and one drawer behaviour across the product. The sticky wrapper carries the position, so `x-nav-drawer`'s own `lg:static lg:h-full` classes are left alone.
- **Rejected**: a full-height layout where only the content scrolls (keeps the bar always on screen, which was not wanted).

## R4. The menu button and its waiting signal

- **Decision**: the button is the shell's (`aria-controls="main-nav"`, `aria-expanded`, "Open the menu"), drawn in the dark bar's colours. When the Support or Changelog count is above zero it carries a small signal dot, `aria-hidden`, with a sentence in `sr-only` beside it (FR-012).
- **Focus**: Escape and the scrim close the drawer and return focus to the button (FR-010). The shell does not do this today; doing it in the shell is out of scope and is noted as a follow-up rather than done here.

## R5. Counts

- **Decision**: the counts keep `AdminNavigationViewModel` and their current wording, and take `x-nav-drawer`'s count style (a muted figure) rather than the design's yellow pill.
- **Rationale**: one count style in every drawer. The menu button's signal (R4) carries the urgency on a phone. Revisit if the figure is missed in review.

## R6. Motion and print

`app.css` already stops transitions under `prefers-reduced-motion`, and `x-nav-drawer` and the scrim are `print:hidden`. Nothing to add.

## R7. After review (2026-11-17)

- The menu button and the scrim stood in `shell` and `admin`, which is the second file of an ARIA contract, so they became `x-menu-button` (a `tone`, and an optional `description` read through `aria-describedby`, since `aria-label` hides the button's own text) and `x-nav-scrim`.
- `x-nav-drawer` traps focus while open (`x-trap.noscroll.inert`, as the modals do), which returns focus to the button and locks the page, is `invisible` while closed below `lg` so Tab skips it, closes when the window widens past `lg`, and is a `div`, its `nav` being the landmark. This reaches settings and support too. The vault sidebar has its own drawer and does not get it.
- The entry key `section` became `currentIsDescendant`: `section` already named a route pattern.
- The counting rule is tested on `AdminNavigationViewModel`, not on the layout.
