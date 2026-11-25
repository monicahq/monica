---
version: alpha
name: Monica
description: A quiet console for a personal notebook. White page, cool grey neutrals, hairlines around surfaces, corners of 6 and 8, no accent colour.
colors:
  primary: "#18181b"
  on-primary: "#fafafa"
  primary-hover: "#27272a"
  page: "#ffffff"
  canvas: "#ffffff"
  card: "#fafafa"
  sunken: "#fafafa"
  input: "#ffffff"
  hairline: "#e4e4e7"
  hairline-strong: "#d4d4d8"
  hairline-soft: "#f4f4f5"
  hover: "#f4f4f5"
  ink: "#09090b"
  ink-soft: "#27272a"
  body: "#52525b"
  muted: "#71717a"
  muted-soft: "#a1a1aa"
  placeholder: "#71717a"
  focus-ring: "#09090b"
  success: "#15803d"
  success-tint: "#f0fdf4"
  success-edge: "#bbf7d0"
  warning: "#b45309"
  warning-tint: "#fffbeb"
  warning-edge: "#fde68a"
  error: "#b91c1c"
  error-hover: "#991b1b"
  error-tint: "#fef2f2"
  error-edge: "#fecaca"
  on-error: "#ffffff"
  query: "#1d4ed8"
  query-tint: "#eff6ff"
  query-edge: "#bfdbfe"
  admin-signal: "#fbbf24"
  dark:
    primary: "#fafafa"
    on-primary: "#18181b"
    primary-hover: "#ffffff"
    page: "#09090b"
    canvas: "#09090b"
    card: "#18181b"
    sunken: "#18181b"
    input: "#09090b"
    hairline: "#27272a"
    hairline-strong: "#3f3f46"
    hairline-soft: "#1f1f23"
    hover: "#27272a"
    ink: "#fafafa"
    ink-soft: "#e4e4e7"
    body: "#d4d4d8"
    muted: "#a1a1aa"
    muted-soft: "#71717a"
    placeholder: "#a1a1aa"
    focus-ring: "#d4d4d8"
    success: "#4ade80"
    success-tint: "#0c2318"
    success-edge: "#14532d"
    warning: "#fbbf24"
    warning-tint: "#271a06"
    warning-edge: "#78350f"
    error: "#f87171"
    error-hover: "#fca5a5"
    error-tint: "#2a1214"
    error-edge: "#7f1d1d"
    on-error: "#18181b"
    query: "#60a5fa"
    query-tint: "#0b1a33"
    query-edge: "#1e3a8a"
  identity:
    blue: "#3f5fd0"
    green: "#4f8a49"
    amber: "#9a6a24"
    rust: "#9a3f2b"
    violet: "#6b4fa8"
    teal: "#2f7f86"
    plum: "#a8447a"
    slate: "#4a4a52"
typography:
  page-title:
    fontFamily: system-ui
    fontSize: 28px
    fontWeight: 600
    lineHeight: 1.15
    letterSpacing: -0.03em
  prose-heading:
    fontFamily: system-ui
    fontSize: 18px
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: -0.02em
  section-title:
    fontFamily: system-ui
    fontSize: 16px
    fontWeight: 600
    lineHeight: 1.35
    letterSpacing: -0.02em
  lede:
    fontFamily: system-ui
    fontSize: 16px
    fontWeight: 400
    lineHeight: 1.55
  card-title:
    fontFamily: system-ui
    fontSize: 15px
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: -0.01em
  body-md:
    fontFamily: system-ui
    fontSize: 13.5px
    fontWeight: 400
    lineHeight: 1.6
  body-sm:
    fontFamily: system-ui
    fontSize: 13px
    fontWeight: 400
    lineHeight: 1.5
  body-xs:
    fontFamily: system-ui
    fontSize: 12.5px
    fontWeight: 400
    lineHeight: 1.4
  label:
    fontFamily: system-ui
    fontSize: 12px
    fontWeight: 500
    lineHeight: 1.4
  nav:
    fontFamily: system-ui
    fontSize: 13.5px
    fontWeight: 500
    lineHeight: 1
  caption:
    fontFamily: system-ui
    fontSize: 11px
    fontWeight: 500
    lineHeight: 1.4
    letterSpacing: 0.08em
  code:
    fontFamily: ui-monospace
    fontSize: 12.5px
    fontWeight: 400
    lineHeight: 1.4
rounded:
  xs: 4px
  sm: 6px
  md: 6px
  lg: 8px
  xl: 8px
  full: 9999px
spacing:
  base: 4px
  section-gap: 32px
  gutter: 16px
  sidebar: 248px
  top-bar: 56px
  reading-column: 960px
  wide-column: 1400px
  admin-column: 1120px
  sm:
    gutter: 24px
motion:
  state-change:
    duration: 150ms
    delay: 0ms
    timingFunction: cubic-bezier(0.22, 1, 0.36, 1)
  menu:
    duration: 170ms
    delay: 0ms
    timingFunction: cubic-bezier(0.22, 1, 0.36, 1)
  surface-enter:
    duration: 220ms
    delay: 0ms
    timingFunction: cubic-bezier(0.22, 1, 0.36, 1)
  toast-enter:
    duration: 260ms
    delay: 0ms
    timingFunction: cubic-bezier(0.22, 1, 0.36, 1)
  toast-exit:
    duration: 200ms
    delay: 0ms
    timingFunction: ease-in
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.nav}"
    rounded: "{rounded.md}"
    height: 36px
    padding: 15px
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
  button-secondary:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    height: 36px
  button-secondary-hover:
    backgroundColor: "{colors.hover}"
  button-ghost:
    textColor: "{colors.body}"
    rounded: "{rounded.md}"
    height: 36px
  button-danger:
    backgroundColor: "{colors.error}"
    textColor: "{colors.on-error}"
    rounded: "{rounded.md}"
    height: 36px
  button-danger-hover:
    backgroundColor: "{colors.error-hover}"
  button-small:
    height: 32px
    padding: 12px
  field:
    backgroundColor: "{colors.input}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    height: 36px
    padding: 12px
  badge:
    backgroundColor: "{colors.hover}"
    textColor: "{colors.body}"
    typography: "{typography.caption}"
    rounded: "{rounded.full}"
    height: 22px
  chip:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.body}"
    rounded: "{rounded.full}"
    height: 32px
  chip-selected:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
  tab:
    textColor: "{colors.muted}"
    typography: "{typography.nav}"
    height: 36px
  tab-selected:
    textColor: "{colors.ink}"
  nav-row:
    textColor: "{colors.body}"
    typography: "{typography.nav}"
    rounded: "{rounded.md}"
    height: 32px
  nav-row-current:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
  panel:
    backgroundColor: "{colors.canvas}"
    rounded: "{rounded.lg}"
    padding: 16px
  notice:
    backgroundColor: "{colors.card}"
    rounded: "{rounded.md}"
    padding: 16px
  row:
    typography: "{typography.body-md}"
    padding: 16px
  admin-bar:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
---

# Monica: design system

Every value below lives in `@resources/css/app.css`. The `@theme` block names the tokens Blade can reach; the `:root` and `.dark` blocks are the only place a hex is written. A feature view uses semantic utilities only: never a raw hex, a pixel radius or a pixel font size.

## Overview

A personal notebook that happens to be powerful. A page about somebody's mother must not feel like a CRM record, a social network, an admin dashboard or a marketing template. It feels quiet, calm, precise and unhurried.

The look is a shadcn-inspired console: a white page, cool grey neutrals, a hairline around anything that is a surface, and no accent colour. Three rules settle most arguments.

1. **User data outranks chrome.** Names, dates, notes and relationships carry more weight than the containers around them.
2. **Colour identifies, it never decorates.** The only saturated colour says something succeeded, needs attention or failed, or it is a colour the user chose (a vault colour, an entity type colour).
3. **Nothing changes under somebody's feet.** No silent reflow, no destructive action without a named consequence, no structure update applied automatically.

WCAG 2.2 AA is a delivery condition, not a phase. Screens are generated from user templates, so each field type is made accessible once, in its component, and every template inherits it. The template builder gets no exemption: it works by keyboard and screen reader.

## Colors

Cool greys, and nothing else until something has gone right or wrong. The top level of the `colors` tokens is the light theme; the `dark` group overrides it when `.dark` is set on the root.

- **Primary (#18181b):** the filled button, a selected chip, the admin bar. It inverts with the theme (#fafafa in dark) rather than keeping a hue of its own.
- **Surfaces:** `page`, `canvas` and `input` are the same white. A panel is separated from the page by its hairline and one pixel of shadow, not by a lighter fill. `card` is the one tinted step: the sidebar, an icon tile, a quiet notice.
- **Hairlines:** `hairline` is every border; `hairline-strong` is the edge of a control and a link underline; `hairline-soft` sits between rows.
- **Text ramp:** `ink`, `ink-soft`, `body`, `muted` and `placeholder` all reach 4.5:1 on page, canvas and card. `muted-soft` does not, and is for marks and micro-labels, never a sentence.
- **Semantic triads:** `success`, `warning` and `error` each come as the word, a `-tint` ground and an `-edge` hairline. `query` is used only by the API documentation method badges. `admin-signal` marks the administrator's own badge.
- **Identity:** the eight `identity` hues are the vault colours of `VaultColourEnum`. They are data, not interface, and changing one is a data migration.

A link is `ink` with a `hairline-strong` underline, and on hover the underline moves, not the word. There is no blue link.

## Typography

One family, the system sans stack. Nothing is downloaded, so the page paints on the first frame and no visitor is announced to a font service. `ui-monospace` is for machine values (ids, keys, slugs, shortcuts) and never for a name or a date. There is no display face.

28px down to 13.5px is the widest gap in the product; everything between is within three pixels, so weight and colour separate a card title from the sentence under it. **600 is for headings, row titles and a name in a list. 500 is for controls, labels, a current navigation row and emphasis.** A page of semibold buttons reads as labels shouting. The `caption` level is uppercase, 11px, tracked at 0.08em, and is the only uppercase text in the product. Fields render at 16px on phones so Safari does not zoom, and return to 13.5px from `sm` up.

## Layout

Base unit 4. A heading and 32px of space is the first tool for separating two things; a panel is the second, kept for a section genuinely independent of what sits above it.

- **Shell** (`@resources/views/layouts/shell.blade.php`): a 248px sidebar in `card` with a hairline down its edge, beside a 56px top bar over a hairline. Below 1024px (`lg`) the sidebar becomes an off canvas drawer opened from the bar.
- **Content column:** 960px, centred, 16px gutter on phones and 24px from `sm`. A `wide` screen (the template builder) takes 1400px and owns its own scrolling. The admin layout reads 1120px under an inverted bar.
- **Footer bar:** sticky at the foot, `canvas` over a top hairline, its contents aligned with the content column.
- **Breakpoints:** Tailwind defaults plus `xs` (35rem) and `topbar-labels` (56.25rem), where the top bar buttons gain their words.

Adapt, do not shrink. Stack regions, reduce columns, keep touch targets. Never scale type down to make a desktop layout fit, and never hide data to save space. Everything holds at 320px and at 200% zoom.

## Elevation & Depth

Depth comes from hairlines, not shadows. There are four shadows and no others.

| Shadow | Value | Where |
| --- | --- | --- |
| `shadow-panel` | `0 1px 2px rgb(0 0 0 / 0.04)` | A panel, under its hairline |
| `shadow-control` | `0 1px 2px rgb(9 9 11 / 0.06)` | A secondary or bar button |
| `shadow-nav-active` | inset 1px `hairline` ring plus `shadow-panel` | The navigation row you are on |
| `shadow-pop` | `0 10px 24px -8px` at 16% ink (60% black in dark) | A menu, a dialog, a toast |

A dialog sits over a `scrim` of ink at 50% (black at 70% in dark).

## Shapes

Corners are two numbers. **6** (`rounded-md`, also `rounded-sm`) is a control: a button, a field, a navigation row, a menu item, an icon tile, a notice. **8** (`rounded-lg`) is a surface: a panel, a menu, a dialog. **4** (`rounded-xs`) is for marks so small that 6 reads as a circle. **Full** is for a badge, a chip and an avatar, and nothing else.

Icons are drawn by the product in `@resources/views/components/icon.blade.php`: a 16 by 16 viewBox, stroke 1.4, no fill, `currentColor`, round caps. No icon library, icon font, unicode symbol or emoji. A missing glyph is drawn in that file. Decorative glyphs are `aria-hidden`; a glyph alone in a control takes a `label`. The logo is never redrawn or recoloured, except inverted on ink.

## Motion & Micro-interaction

Short and plain. Feedback takes 150ms, a menu 170ms, a panel or the drawer 220ms, all on one decelerating curve that replaces Tailwind's `ease-out`. Durations are CSS variables (`duration-(--duration-fast)`) so `prefers-reduced-motion` sets all of them to 0ms in one rule and stops the toast animations outright.

Hover changes a background, nothing more. Nothing moves, scales or lifts, and press does nothing. The toast is the one real animation; its 4500ms timer bar is tied to the auto dismiss timeout in `@resources/views/components/toaster.blade.php`, and the two change together.

## Voice & Content Strategy

- **Tone:** direct, calm, specific and non judgemental. Address the reader as *you*; the application never speaks in the first person. Hard subjects (death, separation, illness) get neutral, respectful wording, with no euphemism and no condolence copy.
- **Capitalisation:** sentence case everywhere, buttons and headings included. Uppercase only for the 11px caption.
- **Terminology:** one word per concept, from the glossary in `@.claude/rules/translations.md`: vault, contact, template, section, field, reminder, life event.
- **Actions:** verbs the user performs: *Add a relationship*, *Save changes*, *Complete reminder*. Never *Submit*, *OK* or *You're all set!*
- **Explain, do not announce:** a supporting line says what a thing is for, not that it exists.
- **Empty states** name what will land there ("Nobody here yet", then what the list will hold). Never "No data".
- **Destructive actions** name the object and the consequence: "Delete this relationship? This removes the link between Alex and Jordan. Both contacts stay."
- **Numbers and dates:** digits; dates as a person says them (14 March 1989, in three weeks). A count goes through `trans_choice()`.
- **Emoji:** never.

## Components

Every shared component is a Blade component in `@resources/views/components/`, most of them anonymous. The pattern is a `$base` class string plus a per variant `match`, merged through `$attributes->class()`. A property a variant chooses (a border colour, a background) is never also named in `$base`: equal specificity means Tailwind's own output order decides, not the string. `@.claude/rules/components.md` lists which component to reach for.

- **Button** (`x-button`): 36px, or 32px small, weight 500. Variants `primary`, `secondary`, `bar`, `ghost`, `danger`, and `inverted` for the admin bar. Secondary and bar sit in `canvas` inside `hairline-strong` with `shadow-control`; on hover the edge darkens to `muted-soft`. Loading keeps the label and the width.
- **Field** (`x-input`, `x-select`, `x-textarea`): 36px, or 32px small, `input` inside `hairline-strong`. Focus adds an ink border and a 3px ring at 20%; an invalid field takes the `error` border and ring. Labels are persistent, never a placeholder.
- **Badge** (`x-badge`): a 22px pill at the caption size. Tones `neutral`, `info`, `success`, `warning`, `danger`, `accent` (filled `primary`), `signal`. The word carries the state; a badge without a word is a bug.
- **Chip** (`x-chip`): a 32px pill filter. Selected fills with `primary` and carries `aria-current` or `aria-pressed`.
- **Tabs** (`x-tabs`): a rule across the full width; the current tab thickens its own piece of it in `primary` and turns `ink`. No pill, no card.
- **Navigation row:** 32px (40px below `lg`), `nav` type. Idle `body`, hover `hover`, current is `canvas` with `shadow-nav-active` at weight 500. Group headings are the caption in `muted-soft`.
- **Panel** (`x-panel`): 8px corners, a hairline, `shadow-panel`, a header ruled off rather than tinted, a 16px body. `flush` lets a list of rows draw its own padding. `tone="danger"` is the only panel with a tinted header.
- **Notice** (`x-notice`): a tinted block with a glyph. `info` is `canvas` in a hairline, `quiet` is `card` in a hairline, the others take their semantic triad.
- **Row** (`x-row`): an outlined 32px icon tile, a semibold title, a `muted` description, right aligned meta, a hairline below. A linked row hovers to `hover` with an inset focus ring.

Focus is a 3px ring of `focus-ring` at 45%, outside the element (`focus-ring`), or inset inside a menu (`focus-ring-inset`), or the field variant (`focus-ring-field`). These recipes are named utilities and are never written out longhand.

**Product patterns.** Contacts are rows, never a grid of cards, grouped by initial only in name order. The vault list is the one screen built from cards. The vault dashboard is six boxes of equal height, each one a link to its part. A relationship is written as a sentence ("Lucie is Théo's mother"), never an arrow. Sensitive fields are masked by default and revealed by a named control, never by hover.

## Do's and Don'ts

- Do keep exactly one primary action per region.
- Do pair every colour with a word or a shape; meaning is never carried by colour alone.
- Do keep text at 4.5:1, and control edges and focus rings at 3:1.
- Do render a refused control with `aria-disabled` and a reason, rather than hiding it.
- Do check every screen in dark, with 5,000 contacts, a 60 character name and German labels.
- Don't use an accent colour, a blue link, glassmorphism, coloured shadows or glowing borders.
- Don't use a gradient as decoration. The few in the product draw data (a reminder ratio, a hatched bar) or the striped edge of the admin bar.
- Don't use a radius other than 4, 6, 8 or full, or a pill shape outside a badge, a chip and an avatar.
- Don't wrap every section in a card; a heading and 32px of space usually does the job.
- Don't use webfonts, third party icons, icon fonts, emoji, stock photos, device mockups or fabricated screenshots.
- Don't put an action behind hover only, or use a placeholder as a label.
- Don't draw a link that leads nowhere, an oversized number filling space, or a decorative dashboard.
