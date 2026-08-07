# Monica v3 — Design guidelines

## 1. Direction

**Reference:** a dense, neutral, border-driven console. Left sidebar with a scope switcher at the top, a quiet content area built from bordered panels and separator-based rows, monospace for machine values, colour used almost nowhere.

**Take from it:** the shell, the panel-with-header, the row density, the restraint.

**Change from it:** contrast. The product ships WCAG 2.2 AA everywhere, so every grey in this document is darker than the console grey it's derived from. Where the reference uses a hairline no one can see, we use a hairline that everyone can see.

**It should feel:** precise, calm, slightly technical, personal without being decorative.

**It should not feel like:** a social network, an enterprise CRM, a wall of cards, a marketing template.

Three rules that decide most arguments:

1. **User data outranks chrome.** Names, dates, notes and relationships get more visual weight than the containers around them.
2. **Structure comes from lines, space and type.** No shadows, no gradients, no glass, no elevation.
3. **Nothing changes under someone's feet.** This is the product's first principle (spec 3.1) and it is a *visual* rule too: no layout that silently re-flows, no destructive action without a named consequence, no auto-applied structure update.

---

## 2. Accessibility contract

AA is a delivery condition, not a phase. Two consequences specific to this product:

**Fields are generated, not hand-built.** A screen is assembled from user-defined templates, so accessibility can't be retrofitted screen by screen. Each *field type* is made accessible once, in its component, and every template inherits it. A template author can produce an ugly page; they must not be able to produce an inaccessible one.

**The builder is a product surface.** Creating a template, reordering sections and editing a repeater must all work with keyboard and screen reader. It's the hardest screen in the product and it doesn't get an exemption.

Non-negotiables on every screen:

- Keyboard-complete, logical tab order, visible focus, no traps outside modals.
- Text 4.5:1, large text 3:1, control boundaries and focus indicators 3:1.
- Meaning never carried by colour alone — always a word or a shape too.
- Persistent labels; placeholders are never labels.
- Readable at 200% zoom and at 320px width.
- `prefers-reduced-motion` removes motion rather than shortening it.
- Errors programmatically tied to their field; dynamic results announced.

---

## 3. Tokens

Semantic tokens only in components. Never a raw hex, px radius or px font size in a feature view.

### 3.1 Colour — light (canonical)

| Token | Value | On white | Use |
|---|---|---|---|
| `--canvas` | `#FFFFFF` | — | Page background |
| `--surface` | `#FFFFFF` | — | Panels, menus, dialogs |
| `--surface-subtle` | `#F7F7F7` | — | Panel headers, inset regions, hover |
| `--surface-muted` | `#EFEFEF` | — | Selected neutral surface |
| `--surface-inverse` | `#171717` | — | Marketing footer, tooltips |
| `--border-subtle` | `#E4E4E4` | 1.3:1 | Row separators only (decorative) |
| `--border` | `#D0D0D0` | 1.6:1 | Panel and section edges |
| `--border-control` | `#8A8A8A` | 3.5:1 | **Inputs, checkboxes, switches, any control edge** |
| `--border-strong` | `#171717` | 17.9:1 | Selected / active boundary |
| `--text` | `#171717` | 17.9:1 | Primary text |
| `--text-secondary` | `#525252` | 7.8:1 | Supporting text |
| `--text-muted` | `#616161` | 6.2:1 | Metadata, placeholders |
| `--text-disabled` | `#767676` | 4.6:1 | Disabled text (still legible) |
| `--text-inverse` | `#FFFFFF` | — | Text on dark |
| `--primary` | `#171717` | — | Primary button fill |
| `--primary-hover` | `#2E2E2E` | — | |
| `--primary-active` | `#000000` | — | |
| `--accent` | `#1D4ED8` | 6.7:1 | Links, selection, active nav |
| `--accent-subtle` | `#EFF4FF` | — | Selected informational surface |
| `--focus` | `#1D4ED8` | 6.7:1 | Focus ring |
| `--success` | `#166534` | 7.1:1 | |
| `--success-subtle` | `#F0FDF4` | — | |
| `--warning` | `#854D0E` | 6.9:1 | |
| `--warning-subtle` | `#FEFAEB` | — | |
| `--danger` | `#B91C1C` | 6.5:1 | |
| `--danger-subtle` | `#FEF2F2` | — | |

Two rules people get wrong:

- `--border-subtle` is **decorative**. If a border is the only thing telling you where a control begins, it must be `--border-control` or darker.
- The accent is **functional**. Links, focus, selection, active nav indicator, informational status. Primary buttons stay near-black.

### 3.2 Colour — dark

Same hierarchy, same border logic. Verify every semantic colour independently — none of these are derived by inversion.

| Token | Value |
|---|---|
| `--canvas` | `#0F0F0F` |
| `--surface` | `#171717` |
| `--surface-subtle` | `#1E1E1E` |
| `--surface-muted` | `#282828` |
| `--border-subtle` | `#2C2C2C` |
| `--border` | `#3D3D3D` |
| `--border-control` | `#767676` |
| `--border-strong` | `#F5F5F5` |
| `--text` | `#F5F5F5` |
| `--text-secondary` | `#C7C7C7` |
| `--text-muted` | `#A1A1A1` |
| `--text-disabled` | `#8A8A8A` |
| `--primary` | `#F5F5F5` (dark text on it) |
| `--accent` | `#8FB3FF` |
| `--success` | `#5CCB82` |
| `--warning` | `#E2B44A` |
| `--danger` | `#F08A8A` |

Never: glowing borders, coloured shadows, pure black everywhere, reduced text contrast, stacked surface layers.

### 3.3 Type

One sans family, one mono. Bundled — no runtime font CDN.

```
--font-sans: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
--font-mono: ui-monospace, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
```

**Application scale**

| Token | Size / weight / line-height | Use |
|---|---|---|
| `page-title` | 20 / 600 / 1.3 | Page heading |
| `section-title` | 15 / 600 / 1.4 | Panel and section headings |
| `body` | 14 / 400 / 1.5 | Default |
| `body-strong` | 14 / 500 / 1.5 | Values that matter |
| `small` | 13 / 400 / 1.45 | Secondary text |
| `caption` | 12 / 500 / 1.4 | Labels, metadata |
| `mono` | 12.5 / 400 / 1.5 | Technical values |
| `button` | 14 / 500 / 1 | Buttons |

App headings never exceed 20px. Personal information is not marketing typography.

**Marketing scale:** 52 / 40 / 30 / 24 display sizes at 600, tight tracking (−0.02em), body 17–18/1.55. Same family as the app.

**Mono is for:** IDs, API tokens and keys, webhook event names, keyboard shortcuts, field slugs, import references, changelog values, code. **Never** for names, notes, journal entries or ordinary dates.

**Rules:** weight before colour for hierarchy; size before weight 700; no uppercase headings (uppercase allowed for 11–12px metadata labels); reading width 65–75 characters; never below 400 for body.

### 3.4 Space, shape, motion

Base unit 4px: `1:4 2:8 3:12 4:16 5:20 6:24 8:32 10:40 12:48 16:64 20:80 24:96`.

App defaults: page padding 24 (16 mobile) · section gap 32 · heading→content 12 · row padding 10/12 · panel body 16 · dialog 20 · field gap 16 · label→control 6 · inline 8. Marketing may breathe: 80–96 between sections.

Radius: `xs 3` indicators · `sm 4` badges · `md 6` inputs, buttons · `lg 8` panels, menus, dialogs · `full` avatars only. Nothing above 8px in the app. Buttons are never pills.

Motion: `fast 100ms` feedback · `standard 150ms` menus · `slow 200ms` dialogs and panels. Allowed: opacity, small translation, height, spinner, progress. Not allowed: spring, bounce, overshoot, parallax, zoom, hover movement.

---

## 4. Shell

```
┌──────────────┬────────────────────────────────────────────┐
│ vault switch │  breadcrumb                    search  ⌘K  │ 56
├──────────────┼────────────────────────────────────────────┤
│ ⌘K search    │  Page title              [Secondary][Add]  │
│              │  ─────────────────────────────────────────  │
│ nav group    │  ┌──────────────────────────────────────┐  │
│  · item      │  │ Panel header             [action]    │  │
│  · item      │  ├──────────────────────────────────────┤  │
│              │  │ row                                  │  │
│ nav group    │  │ row                                  │  │
│  · item      │  └──────────────────────────────────────┘  │
│              │                                            │
│ account      │                                            │
└──────────────┴────────────────────────────────────────────┘
```

Sidebar 248px (collapsed 56) · top bar 56 · page header min 52 · content padding 24 · right detail panel 360–400.

Sticky regions (sidebar, page header, table header, dialog action bar) use a solid background and a one-pixel bottom border. Never a shadow.

Content widths: forms and settings 720 · reading content 720 · contact profile 1120 · tables use the available width · marketing 1200.

---

## 5. Sidebar and the vault switcher

The sidebar has **two scopes**, and it is always obvious which one you're in.

### 5.1 Vault scope

The switcher sits at the very top, above search, and is the first thing in the tab order.

```
┌────────────────────────────┐
│ ▪ Famille              ⌃⌄ │  44px
└────────────────────────────┘
```

- Full-width row, 44px, 8px side margin, radius 6, `--surface-subtle` background, `--border-control` edge.
- Contents: 20px monogram tile (radius 4, neutral fill, 2 initials), vault name at `body-strong` truncated with an accessible full name, up/down chevron 16px right-aligned.
- It is a `<button aria-haspopup="menu" aria-expanded>`. Accessible name: *"Vault: Famille. Change vault."*
- Never colour-coded alone. A vault may carry an accent, but the name and monogram always identify it.

**The menu:** current vault first with a checkmark (a shape, not a highlight) · other vaults, each with contact count · a filter input above the list when there are more than 8 · separator · *Vault settings* · *New vault* · *All vaults*. Solid surface, `--border-strong`, radius 8, 4px padding, 32px items, no shadow. Arrow keys move, Enter selects, Escape closes, focus returns to the trigger.

**Switching is a navigation, not a re-render.** Go to the target vault's home. Never keep the user on the same page with different data underneath — that breaks principle 3.1 and is disorienting on a screen reader. Announce the new scope in a live region.

**Vault navigation:** Journal · People · Entities · Groups · Reminders. Group labels are 12/500 `--text-muted`, sentence case, not uppercase.

### 5.2 Account scope

Settings, structures, marketplace, webhooks, API tokens, changelog. The switcher is replaced by an account row with the same anatomy — no chevron pretending to be a vault picker. A single **← Back to Famille** row returns to the last vault.

### 5.3 Nav items

32px tall, radius 6, 16px icon + 14/400 label, 8px gap. Hover `--surface-subtle`. Active: `--surface-muted`, label at 500, and a 2px accent bar on the leading edge — three signals, only one of which is colour. `aria-current="page"`. Expandable groups use `aria-expanded` and a chevron; the parent row is itself clickable.

Keep the list short. Everything else lives in the command palette (⌘K / Ctrl K): navigation, contact search, create actions, recent contacts, settings, shortcut discovery. Fully keyboard operable, and never the only route to a feature.

---

## 6. Surfaces

**Page** — the canvas. Never inside a card.

**Section** — a semantic group, separated by spacing and a heading. Reach for this first.

**Panel** — a bounded unit with a header. This is the workhorse of the console look:

- 1px `--border`, radius 8, `--surface` body, no shadow.
- Header: `--surface-subtle`, min 44px, `section-title`, optional right-aligned action. Bottom border.
- Body: 16px padding, or zero when it contains rows.

Use a panel when the content is an independent unit — a profile section, a settings group, a reminder list, an import summary. **Do not wrap every paragraph.** If a heading and 24px of space would do the same job, use the heading.

**Inset region** — `--surface-subtle`, no border, for metadata, read-only summaries, provenance and technical values.

**Row group** — related rows separated by 1px `--border-subtle`, no box per item. Default for contact lists, relationships, reminders, activities, settings, custom fields.

---

## 7. Components

**Buttons** — 36px tall (ghost may be 32), radius 6, 14/500, 14px horizontal padding, icon 16px, no shadow.
Primary: `--primary` fill, inverse text. One per region.
Secondary: surface fill, `--border-control` edge.
Ghost: transparent, hover `--surface-subtle`.
Destructive: starts as secondary or ghost; a filled red button appears only at the final confirmation.
Icon button: 32–36 visible, 44 effective target, accessible name required.

**Inputs** — 36px, radius 6, 10px padding, 14px, `--border-control`, `--surface`. Textarea min 96px. Labels 13/500 above the control, always visible. Required stated in text. Errors sit under the field, tied by `aria-describedby`, with an icon and a sentence.

**Select / combobox** — native select unless the user needs search, creation, multi-select or large datasets. Combobox menus: solid surface, border, radius 8, no shadow, full ARIA combobox pattern.

**Checkbox / radio / switch** — native semantics, 16–18px visible, whole label row clickable, custom Monica checkmark. Checked state must have a mark, not just a fill. Switches only for settings that apply immediately; checkboxes for forms that submit.

**Badge** — 22px, 0 8px padding, **radius 4** (not a pill), 12/500, neutral fill by default. Semantic colour only when the status is semantic, and always with a word. Use for: status, activity nature, relationship certainty, field type, sensitivity, marketplace state.

**Tabs** — horizontal row, bottom border across the whole list, active tab in `--text` with a 2px bottom border. No enclosing pill, no card-style active state. Segmented control only for compact mode switches.

**Table** — header 36px, rows 44px (56 when two-line), horizontal separators only, no vertical rules, text left, numbers right, actions in the final column. Row actions are visible on focus and hover but never *only* on hover; important actions keep their label. Rows are not cards. Sortable headers are buttons with an announced state.

**Menu** — solid surface, `--border-strong`, radius 8, 4px padding, 32px items, no shadow, destructive actions after a separator.

**Dialog** — width fits content, border, radius 8, dark translucent backdrop, no shadow. Title, optional description, actions at the bottom, focus trapped and restored, Escape closes unless unsafe. Not for multi-section editing — use a page.

**Side panel** — preserves context while inspecting a contact, reminder, relationship or activity. Left border, no shadow, sticky header, full-screen sheet on mobile.

**Tooltip** — dark surface, light text, 12px, radius 4, no shadow. Explains icon-only controls. Never holds essential instructions or interactive content.

**Toast** — one line, optional undo, auto-dismiss only when safe, persistent when action is required, announced. No stacks.

**Empty state** — one line icon, a title, one sentence, one action. No illustrations. For a small empty section, a single muted sentence is enough. Not every empty box is an onboarding opportunity.

---

## 8. Product patterns

**Contact list** — rows, not a grid of cards. Name, photo or initials, relationship summary, last interaction, next reminder. Real photos allowed; otherwise initials on a neutral fill. Never generate synthetic faces.

**Contact profile** — a stable spine regardless of template: identity header · primary actions · highlighted fields · sections from the assigned template · relationships · timeline · reminders · files · metadata on request. Sections are headings and dividers, panels only where a section is genuinely independent.

**Generated fields** — every field type has one accessible rendering used identically in view, edit, import, search and the API docs. Layout elements (separator, subheading, help text) are real headings and real descriptions, not styled divs. Custom fields look exactly like built-in fields; never demote them into a "custom data" box.

**Sensitive fields** — masked by default, revealed by an explicit control with an accessible name (*"Show national ID"*), the mask indicated in text as well as visually. Never revealed by hover. Excluded from private links by default, and the exclusion is stated where the link is created.

**Relationships** — direction is written, never implied by an arrow: *"Alex is Jordan's parent" / "Jordan is Alex's child."* Show type, qualifiers as badges, period, and certainty (certain / probable / uncertain) as a word. Deduced and suggested relationships are visually distinguishable from confirmed ones and say where they came from. Editing a relationship states the reciprocal that will be created.

**Timeline** — a chronological list. Thin vertical line, date grouping, compact rows, small nature icons, expandable detail, an explicit distinction between event date and recorded date. Activities and relationship events share one stream. No decorative timeline graphics.

**Journal** — a day is a page: activities, the day's own narrative, a mood. An empty day stays empty; no nudging, no streaks, no gamification. Quick capture is a first-class path, not a reduced form — reachable from the palette, a contact, and the day.

**Reminders** — subject, related contact, due date, recurrence, delivery status, state. Overdue uses a word and an icon, not red alone. *Snooze*, *skip*, *complete* and *turn off the rule* are four distinct labelled actions and the interface says which occurrences each one affects.

**Destructive actions** — name the object and the consequence. Not "Are you sure?" but *"Delete this relationship? This removes the link between Alex and Jordan. Both contacts stay."* When something is disabled rather than deleted (a rule whose source field disappeared), say so where the user will look for it, with the reason and a way back.

**Marketplace and versioning** — a template shows its version, its language, its origin and its install count. An available update is an offer: change note, diff, accept or stay. Never a silent apply, never a nag.

---

## 9. States

Every interactive component defines: default, hover, focus-visible, active, selected, disabled, loading, error.

**Hover** — subtle background, border or text change. No movement, no scale, no shadow. Never the only way to find an important action.

**Focus** — `outline: 2px solid var(--focus); outline-offset: 2px;` on every focusable element, `:focus-visible` where appropriate, never removed without an equally visible replacement.

**Loading** — disable the trigger, inline spinner, preserve the button width, keep the label. Skeletons only where they match the final layout; no shimmer under reduced motion. No indefinite spinner without a sentence.

**Disabled** — stays legible (`--text-disabled` at 4.6:1) and explains itself. Never used to hide a permission or a validation rule.

**Announced** — contact created, changes saved, reminder completed, import finished, validation failed, undo done, vault switched. Not hover or decoration.

---

## 10. Responsive

Breakpoints: mobile <640 · tablet 640–1023 · desktop 1024–1439 · wide ≥1440. Driven by content, not device names.

On mobile: sidebar becomes a drawer, with the vault switcher at its top and the current vault named in the header bar. Padding 16. Page actions stay near the title. Multi-column profiles stack. Tables become row lists when horizontal comparison isn't essential. Detail panels become full-screen sheets.

Adapt, don't shrink: reduce columns, stack regions, move non-essential metadata behind detail views, allow horizontal scroll for genuinely tabular data, preserve touch targets. Never scale type down to make a desktop layout fit, and never hide data to save space.

---

## 11. Icons

Original Monica SVGs only, in the Monica repository. No third-party icon libraries, icon fonts, unicode symbols, emoji or raster icons.

24×24 viewBox, rendered at 16/18/20/24, stroke 1.5, fill none, `stroke="currentColor"`, round caps and joins, ~2px optical padding, simple geometry. Filled variants only for selected nav, status marks and very small shapes. Decorative icons `aria-hidden="true"`; icon-only controls carry an accessible name. Don't animate, don't rotate a generic icon to mean something else.

Activity natures carry a user-chosen icon and colour — from our set, never uploaded, and always beside a label.

---

## 12. Marketing

Same tokens, same type, same icons, more whitespace. Not a second brand.

Hero: one headline, one paragraph, one primary action, one optional secondary, one real Monica screen. Feature sections alternate explanation and real interface. Screenshots come from the running product with demonstration data — no device frames, no fake browser chrome, no stock photography, no abstract SaaS illustration, no generated screenshots, no composited controls. Crop deliberately rather than shrinking a desktop screen until its text is unreadable.

Open-source signals belong in plain sight: source, licence, self-hosting, contributing, changelog, privacy. The inverse surface is used once, in the footer.

Voice: direct, calm, human, specific, non-judgemental. *Add a relationship. Save changes. Complete reminder.* Not *Create relationship entity. Submit. You're all set! Level up your relationships.* Death, separation, estrangement, illness and conflict get neutral, respectful wording — this is a product about people someone cares about.

---

## 13. Review checklist

- Could a border here be replaced by spacing? Is anything wrapped in a panel that didn't need one?
- Is the primary action obvious, and is there exactly one per region?
- Are all values from tokens? Any radius above 8? Any raw hex?
- Do control boundaries reach 3:1? Does every status read without colour?
- Complete by keyboard, focus always visible, focus restored after overlays?
- Are hover-only actions reachable another way?
- Does every field type render accessibly in view, edit, import and search?
- Are destructive consequences named, and is nothing applied silently?
- Does it still work with 5,000 contacts, a 40-field template, a 60-character name and German labels?
- Is this real Monica UI, and does the user's own data dominate the screen?

---

## 14. Do not

Shadows · gradients · glassmorphism · a card per section · radii above 8px · pill-shaped buttons · stock photos · device mockups · fabricated screenshots · third-party icons · emoji as icons · hover-only actions · colour-only meaning · placeholder-as-label · oversized numbers filling space · decorative dashboards · social-media-style profiles · animation without a reason · density bought with accessibility.
