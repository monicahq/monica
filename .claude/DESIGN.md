# Monica v3: design guidelines

## 1. Direction

The product is a personal notebook that happens to be extremely powerful. A page about somebody's mother should not feel like opening a Salesforce record.

It should feel quiet, calm, precise and unhurried. It should not feel like a social network, an enterprise CRM, an admin dashboard, a wall of cards, or a marketing template.

**This supersedes the warm paper direction.** Between August 2026 and now this document described terracotta, display typography, a floating dock and low warm shadows. None of that shipped, and some of it never existed in the code at all. What the product actually is, and what this document now describes, is a shadcn-inspired console: a white page, cool grey neutrals, a hairline around anything that is a surface, corners of 6 and 8, and no accent colour whatsoever.

What survived every revision is the accessibility contract, the vocabulary, the voice, the row-based lists, and the rule that a person's own data outranks the chrome around it.

Three rules that decide most arguments:

1. **User data outranks chrome.** Names, dates, notes and relationships get more visual weight than the containers around them.
2. **Colour identifies, it never decorates.** There is no brand hue. The only saturated colour in the interface says a thing succeeded, needs attention or failed, or it is a colour the user chose themselves, which is what a vault colour and an entity type colour are.
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

One deliberate departure from the source design. The drawing this system was taken from sets its help text at `#a1a1aa`, which is 2.6:1 on white. We set it in `--muted`, which measures 4.9:1. The contract in this section outranks the drawing.

---

## 3. Tokens

Every value lives in `resources/css/app.css`. Semantic tokens only in components, never a raw hex, px radius or px font size in a feature view.

The file has two parts. The `@theme` block, which names every token Blade can reach and maps it onto a runtime variable. And the `:root` and `.dark` blocks, which are the only place a hex appears and which are what the theme flips.

There is no `@layer components` and there are no component classes. A button is one utility string in one file rather than a rule here and a class name there.

**The names are ours, the values are shadcn's.** `--ink`, `--hairline`, `--canvas` and `--hover` are not renamed to `foreground`, `border`, `card` and `accent`. Renaming them would touch about nine hundred call sites to change nothing anybody sees, and Tailwind emits nothing at all for a token it does not know, so a single missed site would be silently unstyled rather than loud.

### 3.1 Colour: the palette

Cool greys, and nothing else until something has gone right or wrong.

| Token | Light | Dark | Job |
| --- | --- | --- | --- |
| `--page` | `#ffffff` | `#09090b` | Behind everything |
| `--canvas` | `#ffffff` | `#09090b` | A panel, a dialog, a menu |
| `--card` | `#fafafa` | `#18181b` | A tinted step: the sidebar, a footer band, an icon tile |
| `--sunken` | `#fafafa` | `#18181b` | A read-only well |
| `--input` | `#ffffff` | `#09090b` | A field |
| `--hairline` | `#e4e4e7` | `#27272a` | Every border in the product |
| `--hairline-strong` | `#d4d4d8` | `#3f3f46` | A control edge, a link underline |
| `--hairline-soft` | `#f4f4f5` | `#1f1f23` | Between rows in a list |
| `--hover` | `#f4f4f5` | `#27272a` | Under a pointer |

A panel on the page is the same white as the page. What separates them is the hairline and one pixel of shadow under it. That is the whole idea: a surface is a line around some space, not a lighter rectangle.

The text ramp, measured rather than eyeballed. `ink`, `ink-soft`, `body`, `muted` and `placeholder` each meet 4.5:1 against the page, the canvas and the card. `muted-soft` sits below that and is for marks that are not text, never for a sentence.

| Token | Light | Dark |
| --- | --- | --- |
| `--ink` | `#09090b` | `#fafafa` |
| `--ink-soft` | `#27272a` | `#e4e4e7` |
| `--body` | `#52525b` | `#d4d4d8` |
| `--muted` | `#71717a` | `#a1a1aa` |
| `--muted-soft` | `#a1a1aa` | `#71717a` |
| `--placeholder` | `#71717a` | `#a1a1aa` |

### 3.2 Colour: there is no accent

`--brand`, `--text-link-hover`, `--accent-tint`, `--sand`, `--sand-deep` and the whole `--shell-*` chrome ramp were deleted. A link is `--ink` with a `--hairline-strong` underline, and what moves on hover is the underline rather than the word. A focus ring is `--ink`. The filled button is `--primary`, which is `#18181b` in light and `#fafafa` in dark, so it inverts with the theme rather than keeping a colour of its own.

The one place the product still needs a second ground is the admin bar. It is inverted, `bg-primary text-on-primary`, in both themes. Somebody deactivating a user while believing they are editing their own profile is the failure that bar exists to prevent, and an inverted strip says which of the two products you are in more loudly than the blue tint ever did.

### 3.3 Colour: the names for jobs

Each semantic colour comes in three parts, which is what a badge is built from: the word, the ground it sits on, and the hairline around it.

| Token | Light | Dark |
| --- | --- | --- |
| `--success` / `-tint` / `-edge` | `#15803d` / `#f0fdf4` / `#bbf7d0` | `#4ade80` / `#0c2318` / `#14532d` |
| `--warning` / `-tint` / `-edge` | `#b45309` / `#fffbeb` / `#fde68a` | `#fbbf24` / `#271a06` / `#78350f` |
| `--danger` / `-tint` / `-edge` | `#b91c1c` / `#fef2f2` / `#fecaca` | `#f87171` / `#2a1214` / `#7f1d1d` |

Identity colour is separate and is content rather than interface. The eight hues in `avatar-initials.blade.php` are the vault colours, which are stored in the database as `VaultColourEnum` and `EntityType::COLOURS`. Re-hueing them is a data migration, not a design change.

### 3.4 Type

One family, the system sans stack. Nothing is downloaded, so a page paints on the first frame and no visitor is announced to a font service. The source design sets everything in Geist through a font CDN; we take its sizes, its weights and its tracking, and leave the request behind. The mono stack is for machine values (ids, keys, slugs, shortcuts) and never for a name or a date.

There is no display face. A page title is the same family at a larger size and a heavier weight.

| Step | Size | Weight | Tracking | Job |
| --- | --- | --- | --- | --- |
| `text-page-title`, `text-2xl` | 28 | 600 | -0.03em | The `<h1>` of a screen |
| `text-xl` | 18 | 600 | -0.02em | A heading inside long-form prose |
| `text-section-title`, `text-lg` | 16 | 600 | -0.02em | A section within a screen |
| `text-card-title` | 15 | 600 | -0.01em | A panel header |
| `text-base` | 13.5 | 400 | | Running text |
| `text-sm` | 13 | 400 | | A secondary line |
| `text-xs` | 12.5 | 400 | | A hint, metadata |
| `text-label` | 12 | 500 | | A field label |
| `text-nav` | 13.5 | 500 | | A navigation row, a tab |
| `text-[11px]` + `tracking-caption` | 11 | 500 | 0.08em | The uppercase micro-label |

28 to 13.5 is the widest gap in the product. Everything between is within three pixels of everything else, and what separates a card title from the sentence under it is weight and colour rather than size.

**Controls are medium, not semibold.** A page of semibold buttons reads as a row of labels shouting, and the weight is not what says a button is a button. 600 is for a heading, a row title and the name in a list. 500 is for a control, a label, an active navigation row and emphasis inside a sentence.

### 3.5 Space, shape, motion

Base unit 4.

Corners are two numbers. **6** is a control: a button, a field, a nav row, a menu item, an icon tile. **8** is a surface: a card, a panel, a menu, a dialog. **4** is left for the few marks small enough that 6 reads as a circle. A badge is not on this scale at all, because a badge is fully round, and so is an avatar. Nothing else is.

There is barely an elevation layer.

| Shadow | Value | Where |
| --- | --- | --- |
| `--shadow-panel` | `0 1px 2px rgb(0 0 0 / 0.04)` | A card, under its hairline |
| `--shadow-control` | `0 1px 2px rgb(9 9 11 / 0.06)` | A secondary or bar button, under its edge |
| `--shadow-nav-active` | `inset 0 0 0 1px var(--hairline), 0 1px 2px rgb(0 0 0 / 0.04)` | The navigation row you are on |
| `--shadow-pop` | `0 10px 24px -8px var(--pop-shadow)` | A menu, a dialog, a toast |
| `--shadow-quote` | `0 18px 40px -22px var(--quote-shadow)` | The quote card on the guest screens |

Motion is short and plain: `--duration-fast` 150ms for feedback, `--duration-standard` 170ms for menus, `--duration-slow` 220ms for panels. One easing, which replaces Tailwind's `ease-out`. They are read on a tag as `duration-(--duration-fast)`, and they are variables rather than `@theme` tokens so that the reduced-motion block can zero all of them in one rule instead of putting a `motion-reduce:` variant on every element in the product that moves.

The one real animation is the toast, whose 4500ms timer bar is coupled to the auto-dismiss timeout in `components/toaster.blade.php`. The two have to be changed together.

---

## 4. Shell

`layouts/shell.blade.php` is the signed-in shell: a navigation column on the left that stays put, and the screen itself on the right under a bar that says where you are.

- **Sidebar 248px.** A tinted panel (`--card`) beside the page with a hairline down its edge, not a dark slab against it. What says which side is furniture is the tint and the line, not the contrast. Below 1024px it becomes an off-canvas drawer opened from the bar.
- **Bar 56px, sticky.** The page colour at 85% with an 8px blur behind it, over a hairline. What scrolls under it stays legible instead of disappearing. It carries no shadow.
- **Reading column 960px**, centred, with a 24px gutter and 28px above. It is capped because a settings form set across a 27-inch monitor is not a better settings form.
- **Wide screens** ask for `wide`, which drops the reading cap for a 1400px one and makes the content column exactly as tall as the window, so a region inside it can own the scrolling. The template builder is what this exists for.
- **Footer bar**, when a screen has one, is sticky at the foot and spans the window while its contents line up with the content column.

`layouts/account.blade.php` is the same bar with no sidebar. `layouts/admin.blade.php` is the inverted bar described in 3.2, at 1120px. `layouts/marketplace.blade.php` is the only shell that draws for guests. `layouts/guest.blade.php` is bare.

The sidebar is a slot rather than something the layout builds, because which vault it points at and which destination is current are the screen's business.

### The navigation row

32px minimum, 6px corners, `text-nav`. Idle is `--body` on nothing. Hover is `--hover`. The destination you are on is a small raised card: `--canvas` fill with `--shadow-nav-active`, at weight 500. Group headings above them are the 11px uppercase micro-label in `--muted-soft`.

---

## 5. Surfaces

**A heading and 32px of space is the first tool for separating two things.** A panel is the second, and it is for a section that is genuinely independent of what sits above it. Wrapping every paragraph in one turns a page into a stack of boxes.

`<x-panel>` is the card: 8px corners, a hairline, `--shadow-panel`, a header ruled off rather than tinted, and a 16px body. The footer keeps a tint, because a strip a save button sits in is a different kind of thing from the body above it. `tone="danger"` is the one panel allowed a tinted header, and it is the only panel on a screen entitled to it.

`flush` is for a panel whose body is a list of rows: the rows draw their own padding and hairlines.

`<x-box>` is an older card primitive doing the same job with different markup. Merging the two is a real cleanup and it is not a design change.

---

## 6. Components

Every shared component is an anonymous Blade component in `resources/views/components`. The house pattern is a `@php` block computing a class string with `match`, merged through `$attributes->class([...])`.

Where that pattern names a colour, the shared base string must not name the same one. Two utilities for one property have the same specificity, so what a browser draws is whichever Tailwind emitted last, and Tailwind orders its own output rather than reading the string. `border-transparent` in a base string beat every variant's border colour for as long as the button component had one, which is how a button with an edge in its class list rendered without one. A colour a variant chooses belongs to the variant, in every branch of the `match`.

- **Button.** 36px, or 32px small, at weight 500, 6px corners. Five variants: primary (filled `--primary`), secondary and bar (white inside `--hairline-strong` over `--shadow-control`, drawn identically now that the bar is the same white as the page), ghost, danger. No bottom edge and nothing moves on press, but the secondary button owns its outline rather than borrowing the panel's: a control edged in the same hairline that separates a panel from the page reads as a bordered area rather than something to press, so it steps up to `--hairline-strong` and takes one pixel of shadow. The face stays the page white, so it gains a body without a second ground, and holds up in a footer strip, a sidebar and a card header alike. A tinted face is what this rules out. `--card` is what a footer and a sidebar are already drawn in, so a button filled that way disappears in both.
- **Field.** 36px, or 32px small, 6px corners, white inside `--hairline-strong`. 16px on a phone so Safari does not zoom, and back down from `sm` up.
- **Badge.** A pill. 22px, 11.5px at weight 500. The word carries the state and the tint only agrees with it, so a badge still works with the colour taken away, and a badge with no word in it is a bug.
- **Chip.** A filter or a scope toggle. A pill that fills with `--primary` when it is in force, and carries `aria-current` or `aria-pressed` so the fill is not the only thing saying so.
- **Tabs.** A rule that runs the whole width, and the tab you are on thickens its own piece of it. No enclosing pill, no card, nothing that looks like a button. A per-tab count takes a ground only on the tab you are on.
- **Notice.** A tinted block with a glyph. `info` and `quiet` have no colour left, so they tell themselves apart by their ground: white inside a hairline, and `--card` inside a hairline.
- **Row.** A list row with an outlined icon tile, a title, a description and right-aligned meta.

---

## 7. States

Every interactive component defines: default, hover, focus-visible, active, selected, disabled, loading, error.

**Hover** is a background change to `--hover`, or one step lighter on a filled button. Colour only. Nothing moves, scales or lifts. Never the only way to find an important action. On a secondary or bar button the edge darkens to `--muted-soft` along with the ground, since the outline is what says it is a control and an edge that does not answer the pointer makes the hover read as a tint that landed on the wrong element.

**Press** does nothing. `--press-translate` is 0.

**Focus** is a 3px ring at 45% of `--focus-ring`, outside the element, plus a 3px ring at 20% and a near-black border on a field. It is near black, which is why it is drawn wide and clear of the element rather than tight against it: a near-black ring touching a near-black button would be invisible. Inside a menu, where an outside ring would be clipped, it is an inset ring instead. Never removed without an equally visible replacement.

A secondary or bar button takes the near-black border too, for the reason a field does: its edge carries the control, so leaving it pale under the ring draws the ring around nothing in particular. The filled variants keep the ring alone, having no edge of their own to darken.

**Selected** in navigation is a fill plus a ring. In chips it is a fill plus `aria-pressed`. Never colour alone.

**Loading** disables the trigger, shows an inline spinner, preserves the button width and keeps the label.

**Disabled** stays legible and explains itself. Never used to hide a permission or a validation rule.

**Announced**: contact created, changes saved, reminder completed, import finished, validation failed, undo done, vault switched.

---

## 8. Iconography

The product draws its own icons, in `resources/views/components/icon.blade.php`. **No Lucide, no Heroicons, no icon font, no unicode symbols, no emoji, no raster icons.** A glyph that does not exist in that file has to be drawn in that file.

16×16 viewBox, stroke 1.4, `fill: none`, `currentColor`, round caps and joins, about 2px of optical padding, simple geometry. Rendered at 15 inline, 16 in rows and buttons, 18 in notices, 20 in empty states.

Decorative glyphs are `aria-hidden`. A glyph that is the only content of a control is given a `label`. An unknown name falls back to the default rather than drawing nothing, since an empty square beside a row reads as a bug.

`components/entity-icon.blade.php` is a separate, smaller set, because the names it answers to are stored in the database as an entity type's chosen icon. Renaming those is a migration, not a design change.

The mark in `public/images/logo.svg` is never redrawn and never recoloured, except inverted on ink.

---

## 9. Product patterns

**Contact list.** Rows, not a grid of cards. Name, photo or initials, relationship summary, last interaction, next reminder. In name order the rows are grouped under the letter they begin with, which is what makes a long list something to be scanned; in any other order there are no letters, since a letter over a list ordered by a date is a heading that lies. The chips that narrow it are links, so the list can be reached again by the back button, and the order is two selects and a button rather than a select that reloads the screen the moment it is touched. A run cut in half by the pagination comes back on the next page without its letter, so it reads as the rest of itself.

**Vault list.** The one screen in the product built from cards rather than rows, and the one with no sidebar. A vault is not one of a long list to be scanned down a column: there are three or four, they are picked by recognition, and each carries a sentence about what it is for that a row would truncate. The card's mark is the colour somebody chose for the vault, not a tint derived from its name: the choice is theirs and the screen does not overrule it.

**Vault dashboard.** One box for every part of the vault, on a three-column grid at the content width. The grid is not a wall of cards despite looking like one: there are six of them, they never grow to seven, and a box is a section of the vault rather than a record. The whole box is the link, so the rows inside it are plain spans; a box whose part has nothing in it yet is an empty state naming what will land there and is not a link at all. Boxes stretch to a common height, so no part of the vault reads as smaller than another for having fewer rows in it today.

**Contact profile.** A stable spine regardless of template: identity header, primary actions, highlighted fields, sections from the assigned template, relationships, timeline, reminders, files, metadata on request. Sections are headings and space, panels only where a section is genuinely independent.

**Template builder.** The screen the current design was drawn from. A tab bar of pages, section cards on the left, and a sticky inspector on the right that edits the field you picked. A published version is frozen and says so in a notice above the tabs rather than by disabling controls silently.

**Generated fields.** Every field type has one accessible rendering used identically in view, edit, import, search and the API docs. Layout elements (separator, subheading, help text) are real headings and real descriptions, not styled divs. Custom fields look exactly like built-in fields. Never demote them into a "custom data" box.

**Sensitive fields.** Masked by default, revealed by an explicit control with an accessible name ("Show national ID"), the mask indicated in text as well as visually. Never revealed by hover. Excluded from private links by default, and the exclusion is stated where the link is created.

**Relationships.** Direction is written as a sentence, never implied by an arrow: "Lucie is Théo's mother." Show type, qualifiers as badges, period, and certainty (certain, probable, uncertain) as a word. Deduced and suggested relationships are visually distinguishable from confirmed ones and say where they came from. Editing a relationship states the reciprocal that will be created.

**Timeline.** A chronological list. Thin vertical line, date grouping, compact rows, small nature glyphs, expandable detail, an explicit distinction between event date and recorded date. Life events and relationship events share one stream. No decorative timeline graphics.

**Journal.** A day is a page: life events, the day's own narrative, a mood. An empty day stays empty. No nudging, no streaks, no gamification.

**Reminders.** Subject, related contact, due date, recurrence, delivery status, state. Overdue uses a word and a glyph, not red alone. Snooze, skip, complete and turn off the rule are four distinct labelled actions, and the interface says which occurrences each one affects.

**Destructive actions.** Name the object and the consequence. Not "Are you sure?" but "Delete this relationship? This removes the link between Alex and Jordan. Both contacts stay." When something is disabled rather than deleted, say so where the user will look for it, with the reason and a way back.

---

## 10. Voice

Direct, calm, human, specific and non-judgemental. Plain English sentences, in sentence case, about the person's own life.

Address the reader as *you*. Never refer to the application in the first person. Actions are verbs the user performs: *Add a relationship. Save changes. Complete reminder.* Never *Create relationship entity*, *Submit*, or *You're all set!*

**Explain, do not announce.** A section's supporting line says what the thing is for, not that it exists. "Dates in the next thirty days, from birthdays and anything you asked to be reminded of." Not "Your upcoming dates."

**Empty states name what will land there**, not that the list is empty. "Nobody here yet", then a sentence about what this list eventually holds. Never "No data".

Sentence case everywhere, buttons and section headings included. Uppercase only for the 11px metadata label. British-leaning spelling in the product's own strings (favourite, colour). Numbers as digits. Dates written the way a person says them (14 March 1989, in three weeks, six days ago).

Hard subjects (death, separation, estrangement, illness, conflict) are ordinary content in a personal CRM and get neutral, respectful wording. No euphemism, no jollity, no condolence copy.

Microcopy may have a light touch where it is honest ("so December is not a panic"), but clarity always wins over charm.

**Emoji: never.** Not in the interface, not as icons, not in empty states. Personality comes from the icon set and the writing.

---

## 11. Responsive

Breakpoints: mobile <640, tablet 640 to 1023, desktop 1024 to 1439, wide ≥1440. Driven by content, not device names.

Below 1024 the sidebar becomes a drawer opened from the bar. On mobile the page gutter is 16, page actions stay near the title, multi-column profiles stack, tables become row lists when horizontal comparison is not essential, detail panels become full-screen sheets.

Adapt, do not shrink. Reduce columns, stack regions, move non-essential metadata behind detail views, allow horizontal scroll for genuinely tabular data, preserve touch targets. Never scale type down to make a desktop layout fit, and never hide data to save space.

---

## 12. Marketing

Same tokens, same type, same icons, more whitespace. Not a second brand.

Hero: one headline, one paragraph, one primary action, one optional secondary, one real screen of the product. Feature sections alternate explanation and real interface. Screenshots come from the running product with demonstration data. No device frames, no fake browser chrome, no stock photography, no abstract SaaS illustration, no generated screenshots, no composited controls.

Open-source signals belong in plain sight: source, licence, self-hosting, contributing, changelog, privacy.

---

## 13. Review checklist

- Is anything wrapped in a panel that a heading and 32px of space would have done?
- Is the primary action obvious, and is there exactly one per region?
- Are all values from tokens? Any radius other than 4, 6, 8 or full? Any raw hex in a feature view?
- Is every control at weight 500 and every heading at 600?
- Does every status read without colour? Do control boundaries reach 3:1?
- Complete by keyboard, focus always visible as a ring clear of the element, focus restored after overlays?
- Are hover-only actions reachable another way?
- Does a destination that does not exist yet look like a word rather than a link?
- Does every field type render accessibly in view, edit, import and search?
- Are destructive consequences named, and is nothing applied silently?
- Does it hold up in dark, where the ramp inverts and the semantic hues are lifted?
- Does it still work with 5,000 contacts, a 40-field template, a 60-character name and German labels?
- Is this real product UI, and does the user's own data dominate the screen?

---

## 14. Do not

Gradients (other than the one wash behind the guest quote) · glassmorphism · background images · grain · a card per section · a radius that is not 4, 6, 8 or full · pill-shaped anything except a badge, a chip and an avatar · coloured shadows · glowing borders · an accent colour · a blue link · stock photos · generated faces · device mockups · fabricated screenshots · third-party icons · icon fonts · emoji · webfonts · hover-only actions · colour-only meaning · placeholder-as-label · a link that leads nowhere · oversized numbers filling space · decorative dashboards · social-media-style profiles · animation without a reason · density bought with accessibility.
