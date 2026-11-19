# Research: What’s new with the panda

## R1. A separate image for the dark theme

- **Decision**: a dark version of the panda, `panda-whats-new-dark.webp`, supplied by the person
  asking, shown only in the dark theme with `dark:hidden` and `hidden dark:block`, the pair the 404
  page uses.
- **Rationale**: the light artwork showed no fringe on the dark page, but the person supplied a dark one
  (dark notebook, calendar and bubbles) that sits better there. Same encoding as R2: 32 KB.
- **Alternatives considered**: the light image in both themes (rejected by the person asking).

## R2. Size and encoding

- **Decision**: resize to 360 by 360 (180 pixels displayed at double density), WebP quality 85,
  alpha quality 100: `magick panda.png -resize 360x360 -quality 85 -define webp:alpha-quality=100 panda-whats-new.webp`.
- **Rationale**: 41 KB against 1.7 MB for the PNG, which is 2.4 percent and meets SC-002. Quality 80
  saves 3 KB only.
- **Alternatives considered**: keeping 1254 pixels (rejected: forty times the bytes for no visible gain).

## R3. The header row

- **Decision**: in `index.blade.php`, wrap `x-page-header` and the `<img>` in a flex row, centred
  vertically, space between. The image is `size-24` on phones and `size-45` (180 pixels) from `sm`,
  `flex-none`, with `alt=""`, `width` and `height` set, `decoding="async"`.
- **Rationale**: the design centres the panda against the title block. `x-page-header` has an
  `actions` slot, but it is for what you may do there and aligns to the top. The wrapper stands in
  one file, so `.claude/rules/components.md` says leave it there.
- **Alternatives considered**: the `actions` slot (rejected: wrong meaning, wrong alignment); hiding
  the panda on phones (rejected: shrinking keeps it and the title fits beside it).

## R4. The feedback row

- **Decision**: the comment control becomes `x-icon-button` with `icon="comment"` and
  `:label="$entry['commentLabel']"`, keeping `aria-controls`, `x-bind:aria-expanded` and the
  `@click` that loads the comments. It sits before the vote group, after an `aria-hidden` one pixel
  rule, the three wrapped in one `ml-auto` flex. The vote group keeps `id="changelog-vote-…"`, its
  `role="group"` and its `aria-label`. The live span becomes `sr-only` and says nothing until a vote
  exists, then “Thanks, noted”.
- **Rationale**: `x-icon-button` is the house glyph button, already named and with a tooltip. Its own
  `x-data` nests inside the row’s, so `open` and `loaded` stay reachable. `commentLabel` already
  carries the count (“Comment”, “2 comments”), tested in `ChangelogViewModelTest`. The vote answers
  with `back()` and Alpine Ajax swaps the element whose id it targets, so the comment button stays
  outside that element and keeps its state.
- **Alternatives considered**: a visible count beside the icon (rejected: not drawn); dropping the
  live span (rejected: the screen reader would lose the confirmation, FR-008).

## R5. The comment glyph

- **Decision**: add `comment` to `x-icon`, with the speech bubble path from the design, drawn on the
  same 16 grid as `thumbs-up`.
- **Rationale**: no bubble glyph exists (`mail`, `note` mean something else). One word, one meaning.

## R6. Tests

- **Decision**: one assertion in `ChangelogControllerTest` that the screen no longer shows
  “Do you like it?” and holds the illustration; run the changelog tests, then the full suite, since
  `x-icon` is shared.
- **Rationale**: `.claude/rules/testing.md` allows copy and the presence of a block, and forbids
  asserting classes. Everything else is look.
