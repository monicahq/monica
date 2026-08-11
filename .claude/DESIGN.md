# Monica v3: design guidelines

## 1. Direction

The product is a beautifully designed personal notebook that happens to be extremely powerful. A page about somebody's mother should not feel like opening a Salesforce record.

It should feel warm, calm, confident, tactile, slightly quirky, and unmistakably its own.

It should not feel like a social network, an enterprise CRM, an admin dashboard, a wall of cards, or a marketing template.

**This supersedes the v2 direction.** Until August 2026 this document described a dense, neutral, border-driven console: left sidebar, no shadows, almost no colour, headings capped at 20px. That is gone. What survived the change is the accessibility contract, the vocabulary, the voice, the row-based lists, and the rule that a person's own data outranks the chrome around it. What changed is everything visual: warm paper instead of neutral white, terracotta instead of near-monochrome, a floating dock instead of a sidebar, display typography for page titles, and low warm shadows instead of borders alone.

Three rules that decide most arguments:

1. **User data outranks chrome.** Names, dates, notes and relationships get more visual weight than the containers around them.
2. **Colour identifies, it never decorates.** A hue says what kind of thing something is. Most of a screen stays neutral, so a screenshot reads as calm but alive.
3. **Nothing changes under somebody's feet.** This is the product's first principle (spec 3.1) and it is a visual rule too. No layout that silently reflows, no destructive action without a named consequence, no auto-applied structure update.

---

## 2. Accessibility contract

AA is a delivery condition, not a phase. Two consequences specific to this product:

**Fields are generated, not hand-built.** A screen is assembled from user-defined templates, so accessibility cannot be retrofitted screen by screen. Each *field type* is made accessible once, in its component, and every template inherits it. A template author can produce an ugly page. They must not be able to produce an inaccessible one.

**The builder is a product surface.** Creating a template, reordering sections and editing a repeater must all work with keyboard and screen reader. It is the hardest screen in the product and it does not get an exemption.

Non-negotiables on every screen:

- Keyboard-complete, logical tab order, visible focus, no traps outside modals.
- Text 4.5:1, large text 3:1, control boundaries and focus indicators 3:1.
- Meaning never carried by colour alone. Always a word or a shape too.
- Persistent labels. Placeholders are never labels.
- Readable at 200% zoom and at 320px width.
- `prefers-reduced-motion` removes motion rather than shortening it.
- Errors programmatically tied to their field. Dynamic results announced.

---

## 3. Tokens

Every value lives in `resources/css/app.css`. Semantic tokens only in components, never a raw hex, px radius or px font size in a feature view.

The file has three parts. The base palette, which is the only place a hex appears. The names for jobs (`--page`, `--ink`, `--action-primary`), which are what Blade reads and which is where the theme flips. And the design system's own component classes at the foot, in `@layer components`.

### 3.1 Colour: the palette

Warm paper neutrals derived from the mark's ink, plus seven earthy object hues. No neon, no SaaS violet gradient.

| Token | Light | Dark | Use |
|---|---|---|---|
| `--paper` | `#FBF8F3` | `#16130F` | The page. Every surface sits on paper, not on white |
| `--canvas` | `#FFFFFF` | `#211D18` | Panels and controls sitting *on* the paper |
| `--sand` | `#F4EEE4` | `#2A251E` | Hover, inset regions, neutral marks |
| `--sand-deep` | `#EBE3D5` | `#332D24` | Selected neutral surface |
| `--hairline` | `#E7DFD2` | `#302A22` | Row separators |
| `--hairline-strong` | `#D6CBB8` | `#423B31` | Control edges |
| `--ink` | `#2C2B29` | `#F4EFE6` | Primary text, the inverse surface |
| `--ink-soft` | `#4A463F` | `#DED7CA` | Ghost button text |
| `--body` | `#5C5850` | `#B6AFA2` | Supporting text |
| `--muted` | `#7D766A` | `#8E8577` | Metadata, row descriptions |
| `--placeholder` | `#A69E90` | `#6F675B` | Placeholders, and what has not been built yet |

The seven object hues, each with a `-tint` used as the background of icon marks, badges and empty-state glyphs, and most with a `-deep` for text on the tint:

| Hue | Light | Carries |
|---|---|---|
| `--terracotta` | `#C0512C` | People, primary actions, the brand |
| `--indigo` | `#3F5FD0` | Journal, information, the focus ring |
| `--forest` | `#2F7350` | Places, activities, success |
| `--marigold` | `#E0A02E` | Pets, warning (as `--marigold-deep`) |
| `--plum` | `#7A4B86` | Reminders |
| `--rose` | `#BC4A64` | Family, gifts |
| `--sky` | `#2E7A96` | Organisations |

In dark the same hues are lifted so they still read as themselves on a dark ground. None of it is derived by inversion. Verify every semantic colour independently.

Never: glowing borders, coloured shadows, pure black everywhere, reduced text contrast, stacked surface layers.

### 3.2 Colour: the names for jobs

`--action-primary` is terracotta and `--action-strong` is ink. Terracotta is the everyday primary. Ink is for the one control that outranks it in a region, and for the vault chip.

`--focus` and `--focus-ring` are two different jobs and must not be merged again. `--focus` is the warm border a field takes under the pointer and while it is being typed in. `--focus-ring` is indigo and is the ring every focusable element draws when the keyboard reaches it.

Semantic colour (`--success`, `--warning`, `--danger`) is always paired with a word and an icon.

### 3.3 Type

Three families, all bundled through `bunny()` in `vite.config.js`. No runtime font CDN.

```
--font-display: 'Bricolage Grotesque'   page titles, person names, marketing
--font-sans:    'Hanken Grotesk'        the whole interface
--font-mono:    'IBM Plex Mono'         machine values only
```

The scale is written as `font` shorthands, so one declaration sets family, weight, size and leading:

| Token | Value | Use |
|---|---|---|
| `--text-display-1` | 600 52/1.02 display | Marketing |
| `--text-display-2` | 600 40/1.06 display | A person's name |
| `--text-page-title` | 600 32/1.1 display | Page heading |
| `--text-section-title` | 600 21/1.25 display | Section heading |
| `--text-card-title` | 600 17/1.35 sans | Panel heading |
| `--text-body` | 400 15/1.55 sans | Default |
| `--text-body-strong` | 600 15/1.5 sans | Row titles, values that matter |
| `--text-small` | 400 14/1.5 sans | Secondary text |
| `--text-caption` | 500 12.5/1.4 sans | Labels, metadata |
| `--text-nav` | 600 13.5/1 sans | The dock |
| `--text-mono` | 400 12.5/1.5 mono | Technical values |

Tracking: `-0.022em` on display, `-0.015em` on titles, `+0.04em` on uppercase captions.

The classes `.erica-page-title`, `.erica-section-title`, `.erica-lede`, `.erica-small` and `.erica-caption` apply these, so a heading is one class rather than six utilities copied correctly every time.

App titles are deliberately large. This is the one place the system lets typography be loud, and it is the clearest break from v2, which capped headings at 20px.

Mono is for ids, API tokens and keys, webhook event names, keyboard shortcuts, field slugs, import references and code. Never for a name, a note, a journal entry or a date.

Rules: weight and space carry hierarchy before a border does. No uppercase headings (uppercase is allowed for 11 to 12.5px metadata labels). Reading measure capped at 66 characters. Never below 400 for body.

### 3.4 Space, shape, motion

Base unit 4px: `1:4 2:8 3:12 4:16 5:20 6:24 8:32 10:40 12:48 16:64 20:80 24:96`.

Layout constants: page padding 24 (16 on a phone), section gap 32, row padding 14/16, panel body 20, field gap 16. Content column 1120, reading and form columns 720, journal 840. The page reserves 120px at the bottom for the dock.

Radius: `xs 5` indicators, `sm 8` marks and badges, `md 10` buttons and fields, `lg 14` panels and cards, `xl 20` the dock. Nothing above 20. Nothing is a pill except an avatar and a person pill.

Elevation is object-like, not floating: a hard 1px bottom edge plus a low, warm, tightly spread shadow. `--shadow-raised` on buttons and panels, `--shadow-lift` on a lifted surface, `--shadow-pop` on the dock, the launcher and menus. Fields go the other way, with a soft inset shade (`--shadow-press-inset`), so they read as a well rather than a box. In dark the shadows become black, because a warm shadow on a dark ground is invisible.

Motion: `fast 110ms` feedback, `standard 170ms` menus, `slow 220ms` dialogs and panels, all on `cubic-bezier(.22, 1, .36, 1)`. Allowed: opacity, small translation, height, the toast's draining timer bar. Not allowed: spring, bounce, overshoot, parallax, zoom, hover movement.

No background images, no gradients, no patterns, no grain, no glassmorphism. The only translucency in the system is the sticky top bar (90% paper and a 10px blur) and the launcher scrim (28% ink).

---

## 4. Shell

```
┌──────────────────────────────────────────────────────────────┐
│ ← Back to your vaults                          ☀   (RF)      │ 60
├──────────────────────────────────────────────────────────────┤
│                                                              │
│                        Page title                            │
│                    one line about it                         │
│                                                              │
│   Section heading      what the section is for               │
│   ┌────────────────────────────────────────────────────┐    │
│   │ ▪  Row title                              meta  ›  │ 72 │
│   │ ▪  Row title                              meta  ›  │    │
│   └────────────────────────────────────────────────────┘    │
│                                                              │
│              ┌──────────────────────────────────┐            │
│              │ 🐱 Famille ⌄ │ ♥  👥  📖  🔔     │ 72        │
│              └──────────────────────────────────┘            │
└──────────────────────────────────────────────────────────────┘
```

`layouts/shell.blade.php` is the shell of a screen navigated by the dock. A sticky 60px bar at the top, one column in the middle capped at the reading width, the dock at the foot of the window. There is no column of links down the left. That is what the dock replaced.

`layouts/app.blade.php` and `layouts/account.blade.php` are the v2 shells and are still what the unmigrated screens use. They will be retired as those screens move to `shell`.

`shell` takes a `wide` flag, which swaps the 720px reading column for the 1120px content column. It is for a screen with two columns of its own, such as the template builder and the field-type picker, and not for making a form roomier: a form at 1120px is a form whose labels are a long way from their fields.

As of August 2026 every screen under `/settings` is on `shell`. Nothing outside it has moved: the vault screens, the contact screens, getting started and the auth screens are all still on the v2 shells. They have the new palette and the new type, because those are global, but the old structure around them.

Sticky regions use a translucent paper background, a blur, and a one-pixel bottom border.

---

## 5. Navigation: the dock and the launcher

The dock is fixed at the bottom centre of the window and carries **four destinations and nothing else**. Four is the whole point: they are the places somebody goes every day, they keep their order forever, and each one is named rather than left as an icon to be learned. The dock never shows an icon alone.

Anatomy. The vault chip on the left is an ink-filled 60px button with the mark on a light plate, the vault's name, and a chevron that turns over when its menu is open. A hairline rule. Then the four destinations, each a 70px minimum column of a 36×26 glyph plate above a 13.5px label.

States. **Rest** is a muted label and glyph with no plate. **Hover** is a warm sand tint and darker text in 110ms, colour only, nothing moves or grows. **Press** moves the item down exactly 1px and comes back, and that single pixel is the whole physicality of the control. **Where you are** gives the glyph a plate of its own colour and the label goes to ink, carried by `aria-current="page"` and never by colour alone. **Focus** is a 2px indigo ring at 2px offset. A **count** appears only when something is actually waiting.

Below 720px the dock stops floating and stretches between the edges. The chip keeps its mark and drops its label, and the four destinations share what is left.

**The vault chip is not a fifth destination.** It is the way out of the four. It opens the launcher, which holds everything rarer: the other things in this vault, the other vaults, the settings, signing out. The launcher rises 220ms above the dock, a scrim dims the page behind it, and the search field takes focus on open. `⌘K` opens it from anywhere and `esc` leaves it. Leaving it never chooses anything.

**A screen that belongs to the account rather than to a vault marks nothing as current.** You reached it from the launcher, and marking a dock item would claim otherwise. The chip still points at a vault, being the account's default one or the first it made, so the four destinations always lead somewhere.

**A destination that has not been built yet is drawn and not offered.** It takes `--placeholder` and is a `<span>` rather than an `<a>`. A link that leads nowhere is worse than a word that does not move. The journal, reminders, tasks and gifts are all in this state today.

The word "Home" is unavailable for the first destination: it is already the label an address or a phone number carries, and it is translated as somebody's residence in every locale. That destination is called "Dashboard", which is what this codebase has always called the screen it opens.

---

## 6. Surfaces

**Page.** The paper. Never inside a card.

**Section.** A semantic group, separated by 32px and a heading, with one line beside the heading saying what the section is for. Reach for this first. A heading and 32px of space is the first tool for separating two things. A panel is the second.

**Panel** (`<x-panel>`). A bounded unit with an optional header carrying a 28px mark in the colour of whatever kind of thing it holds. 1px hairline, radius 14, canvas body, `--shadow-raised`. Body padding 20, or zero (`:flush="true"`) when it contains rows, because rows draw their own padding and their own separators.

Use a panel when the content is genuinely independent of what sits above it: a profile section, a settings group, a reminder list, an import summary. **Do not wrap every paragraph.**

**Row** (`<x-row>`). One line of a list: a 32px mark, a title at `--text-body-strong`, a sentence at `--text-small` saying what is behind it, and whatever belongs at the far end. Rows are separated by hairlines and the last one drops its rule. A row with an `href` is the link, all of it. A row without one is a plain division, still drawn but with nothing suggesting it can be pressed.

Lists are rows, never a grid of little cards, because a row can be read down a column and a card cannot. This is the default for contact lists, relationships, reminders, activities, settings and custom fields.

**Inset region.** `--sand`, no border, for metadata, read-only summaries, provenance and technical values.

---

## 7. Components

**Buttons.** `.erica-btn` plus a variant and a size. 34px small, 40px default, 48px page-level. Radius 10, weight 600, gap 8, glyph 18. Press moves down 1px and drops the shadow.
`--primary` is terracotta fill with a darker bottom edge. One per region.
`--strong` is ink fill, for the control that outranks the primary.
`--secondary` is canvas fill with a `--hairline-strong` edge.
`--ghost` is transparent, hovering to sand.
`--danger` is a filled red button that appears only at the final confirmation. A destructive action starts as secondary or ghost.

**Icon button.** `.erica-iconbtn`, 34 or 40 visible, effective target 6px larger on every side. Always carries an accessible name, and never holds the only route to something.

**Fields.** `.erica-field` is the label, the control, the hint and the error, 6px apart and wired together by id. A value that cannot be changed is not a disabled field: a disabled box invites clicking and submits nothing, so what is shown instead is the value itself on a sunken plate, in mono when it is a machine value. Settled slugs and a field's type are both written that way. `.erica-control` is the control itself: 44px, radius 10, `--hairline-strong` edge, and an inset shade so the field reads as a well rather than a box. It is the one place in the system where elevation points inwards. Focus takes an ink border and a terracotta halo. `--sm` is 36px, `--textarea` grows and may be dragged, `--select` draws its own chevron, `--file` drops the well and dresses the browser's own button as a small secondary one. Every control is 16px below 640px, because Safari on iOS zooms the page in for anything smaller.

Labels are always visible, 13px, above the control. Required is stated in text. Errors sit under the field, tied by `aria-describedby`, and carry a glyph as well as the colour.

**Select and combobox.** Native select unless the user needs search, creation, multi-select or large datasets.

**Checkbox, radio, switch.** Native semantics, 16 to 18px visible, whole label row clickable, a real tick rather than a fill alone. Switches only for settings that apply immediately, checkboxes for forms that submit.

**Tabs.** `.erica-tabs` and `.erica-tab`: a rule runs the whole width of the list and the tab you are on thickens its own piece of it. No enclosing pill, no card-style active state, nothing that looks like a button. A tab is a link when the face it opens has an address, which it should, and it carries `aria-selected`. A count beside the label sits in `.erica-tab__count`.

**Chip.** `.erica-chip`, a filter or scope toggle, 36px, radius 10. What is in force is a filled ink chip rather than a tinted outline, so which one it is never comes down to noticing that one border is slightly darker than the others. It carries `aria-current` as a link and `aria-pressed` as a button, so the fill is not the only signal. A filter that has a URL is a link, so it can be shared and reached by the back button.

**Badge.** `.erica-badge`, 24px, radius 5, never a pill, 12.5px, neutral fill by default. Tones: neutral, success, warning, danger, info, accent, outline. The word inside it is the signal and the tint only repeats what the word already says, so a badge with no word in it is a bug. A coloured dot with the word hidden behind `sr-only` is not a badge: it is colour alone for everybody who can see it.

**Menu.** `.erica-menu`: canvas surface, `--hairline-strong`, radius 14, `--shadow-pop`, 6px padding, destructive actions after a separator. What is already chosen is heavier and sits on a tint as well as carrying a tick, so it is not the tick alone that has to be spotted. Escape closes, focus returns to the trigger.

A menu is pinned to its trigger with `x-anchor`, which takes it out of the flow. It has to be: a panel clips what overflows it, or a row's hover tint would square off the corner it sits in, so a menu positioned inside one would be cut off at its edge. A menu that only picks a value builds its choices out of real submit buttons, so the choice still saves with javascript switched off.

**Dialog.** Width fits content, border, radius 14, `--shadow-pop`, and `.erica-scrim` behind it. Title, optional description, actions at the bottom. Focus is trapped by `x-trap.noscroll.inert` and returned to the trigger, because a dialog that claims `aria-modal` while the tab key can walk out behind it is lying. Escape closes unless unsafe. Not for multi-section editing, which is a page.

**Notice.** `.erica-notice`, radius 10, attached to the thing it is about rather than a banner at the top of a screen saying something about somewhere else. Tones: quiet (the default, an aside that is neither good news nor bad), info, success, warning, danger. The glyph carries the tone alongside the tint, so it still reads as a warning for somebody who cannot see the colour.

**Toast.** One line, optional undo, auto-dismiss only when safe, persistent when action is required, announced. No stacks.

**Empty state.** A 64px glyph on its tint, tilted four degrees, a title, one sentence, one action. No illustrations. A small empty section only needs a single muted sentence. Not every empty box is an onboarding opportunity.

**Avatar.** Somebody's own photograph, or their initials on one of seven warm tints picked from the letters of their name, so the same person is always the same colour. Never a generated face, never stock photography.

---

## 8. Iconography

The product draws its own icons, in `resources/views/components/icon.blade.php`. **No Lucide, no Heroicons, no icon font, no unicode symbols, no emoji, no raster icons.** A glyph that does not exist in that file has to be drawn in that file.

16×16 viewBox, stroke 1.4, `fill: none`, `currentColor`, round caps and joins, about 2px of optical padding, simple geometry. Rendered at 16 inline, 18 in rows and buttons, 20 in panel headers, 23 in the dock, 30 in empty states. Filled variants only for the favourite star and status marks.

Decorative glyphs are `aria-hidden`. A glyph that is the only content of a control is given a `label`, which makes it an image with a title. An unknown name falls back to the default rather than drawing nothing, since an empty square beside a row reads as a bug.

`components/entity-icon.blade.php` is a separate, smaller set, because the names it answers to are stored in the database as an entity type's chosen icon. Renaming those is a migration, not a design change.

The mark in `public/images/logo.svg` is never redrawn and never recoloured, except inverted on ink. There is no wordmark file. The product name is set in display type beside the mark.

---

## 9. Product patterns

**Contact list.** Rows, not a grid of cards. Name, photo or initials, relationship summary, last interaction, next reminder.

**Contact profile.** A stable spine regardless of template: identity header, primary actions, highlighted fields, sections from the assigned template, relationships, timeline, reminders, files, metadata on request. Sections are headings and space, panels only where a section is genuinely independent.

**Generated fields.** Every field type has one accessible rendering used identically in view, edit, import, search and the API docs. Layout elements (separator, subheading, help text) are real headings and real descriptions, not styled divs. Custom fields look exactly like built-in fields. Never demote them into a "custom data" box.

**Sensitive fields.** Masked by default, revealed by an explicit control with an accessible name ("Show national ID"), the mask indicated in text as well as visually. Never revealed by hover. Excluded from private links by default, and the exclusion is stated where the link is created.

**Relationships.** Direction is written as a sentence, never implied by an arrow: "Lucie is Théo's mother." Show type, qualifiers as badges, period, and certainty (certain, probable, uncertain) as a word. Deduced and suggested relationships are visually distinguishable from confirmed ones and say where they came from. Editing a relationship states the reciprocal that will be created.

**Timeline.** A chronological list. Thin vertical line, date grouping, compact rows, small nature glyphs, expandable detail, an explicit distinction between event date and recorded date. Activities and relationship events share one stream. No decorative timeline graphics.

**Journal.** A day is a page: activities, the day's own narrative, a mood. An empty day stays empty. No nudging, no streaks, no gamification.

**Reminders.** Subject, related contact, due date, recurrence, delivery status, state. Overdue uses a word and a glyph, not red alone. Snooze, skip, complete and turn off the rule are four distinct labelled actions, and the interface says which occurrences each one affects.

**Destructive actions.** Name the object and the consequence. Not "Are you sure?" but "Delete this relationship? This removes the link between Alex and Jordan. Both contacts stay." When something is disabled rather than deleted, say so where the user will look for it, with the reason and a way back.

---

## 10. Voice

Direct, calm, human, specific and non-judgemental. Plain English sentences, in sentence case, about the person's own life.

Address the reader as *you*. Never refer to the application in the first person. Actions are verbs the user performs: *Add a relationship. Save changes. Complete reminder.* Never *Create relationship entity*, *Submit*, or *You're all set!*

**Explain, do not announce.** A section's supporting line says what the thing is for, not that it exists. "Dates in the next thirty days, from birthdays and anything you asked to be reminded of." Not "Your upcoming dates."

**Empty states name what will land there**, not that the list is empty. "Nobody here yet", then a sentence about what this list eventually holds. Never "No data".

Sentence case everywhere, buttons and section headings included. Uppercase only for 11 to 12.5px metadata labels. British-leaning spelling in the product's own strings (favourite, colour). Numbers as digits. Dates written the way a person says them (14 March 1989, in three weeks, six days ago).

Hard subjects (death, separation, estrangement, illness, conflict) are ordinary content in a personal CRM and get neutral, respectful wording. No euphemism, no jollity, no condolence copy.

Microcopy may have a light touch where it is honest ("so December is not a panic"), but clarity always wins over charm.

**Emoji: never.** Not in the interface, not as icons, not in empty states. Personality comes from the icon set, the colour and the writing.

---

## 11. States

Every interactive component defines: default, hover, focus-visible, active, selected, disabled, loading, error.

**Hover** is a warm sand tint, or one step darker on a filled button. Colour only. No movement, no scale, no shadow change. Never the only way to find an important action.

**Press** moves the control down `--press-translate` and drops its shadow.

**Focus** is `outline: 2px solid var(--focus-ring); outline-offset: 2px` on every focusable element, never removed without an equally visible replacement.

**Selected** in navigation and chips is a solid fill with a colour change, never colour alone.

**Loading** disables the trigger, shows an inline spinner, preserves the button width and keeps the label. No indefinite spinner without a sentence.

**Disabled** stays legible and explains itself. Never used to hide a permission or a validation rule.

**Announced**: contact created, changes saved, reminder completed, import finished, validation failed, undo done, vault switched.

---

## 12. Responsive

Breakpoints: mobile <640, tablet 640 to 1023, desktop 1024 to 1439, wide ≥1440. Driven by content, not device names.

On mobile the dock stretches edge to edge and drops the vault label, page padding is 16, page actions stay near the title, multi-column profiles stack, tables become row lists when horizontal comparison is not essential, detail panels become full-screen sheets.

Adapt, do not shrink. Reduce columns, stack regions, move non-essential metadata behind detail views, allow horizontal scroll for genuinely tabular data, preserve touch targets. Never scale type down to make a desktop layout fit, and never hide data to save space.

---

## 13. Marketing

Same tokens, same type, same icons, more whitespace. Not a second brand. Display sizes go up to 52.

Hero: one headline, one paragraph, one primary action, one optional secondary, one real screen of the product. Feature sections alternate explanation and real interface. Screenshots come from the running product with demonstration data. No device frames, no fake browser chrome, no stock photography, no abstract SaaS illustration, no generated screenshots, no composited controls.

Open-source signals belong in plain sight: source, licence, self-hosting, contributing, changelog, privacy. The ink surface is used once, in the footer.

---

## 14. Review checklist

- Is anything wrapped in a panel that a heading and 32px of space would have done?
- Is the primary action obvious, and is there exactly one per region?
- Are all values from tokens? Any radius above 20? Any raw hex in a feature view?
- Does every hue identify a kind of thing rather than decorate?
- Does every status read without colour? Do control boundaries reach 3:1?
- Complete by keyboard, focus always visible with the indigo ring, focus restored after overlays?
- Are hover-only actions reachable another way?
- Does a destination that does not exist yet look like a word rather than a link?
- Does every field type render accessibly in view, edit, import and search?
- Are destructive consequences named, and is nothing applied silently?
- Does it hold up in dark, where the shadows are black and the hues are lifted?
- Does it still work with 5,000 contacts, a 40-field template, a 60-character name and German labels?
- Is this real product UI, and does the user's own data dominate the screen?

---

## 15. Do not

Gradients · glassmorphism · background images · grain · a card per section · radii above 20px · pill-shaped anything except an avatar · coloured shadows · glowing borders · stock photos · generated faces · device mockups · fabricated screenshots · third-party icons · icon fonts · emoji · hover-only actions · colour-only meaning · placeholder-as-label · an icon in the dock without a label · a link that leads nowhere · oversized numbers filling space · decorative dashboards · social-media-style profiles · animation without a reason · density bought with accessibility.
