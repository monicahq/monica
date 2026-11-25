# Feature Specification: Screenshots in What's new

**Feature Branch**: `2026-11-25-changelog-screenshots`
**Created**: 2026-11-25
**Status**: Draft
**Input**: User description: "je veux qu'on puisse supporter des screenshots dans le changelog. on doit pouvoir avoir un ou plusieurs screenshots. quand on clique sur un screenshot, on doit pouvoir le voir en plein ecran. les screenshots doivent etre stockés en webp, dans un dossier changelog dans le repertoire public. le nom du fichier doit matché celui du changelog avec l'id unique afin de pouvoir le retrouver facilement. je pense (mais à toi de me challenger) qu'on peut utiliser le frontmatter pour tout cela." Then: an entry about a visual change carries screenshots wherever possible, taken with Iris, zoomed on the relevant element, of the step that shows the most value only. Design reference: `Changelog.dc.html` in the claude.ai design project 94638d7f-e5c5-4d6f-b079-ee599675bc84.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See what changed, not only read about it (Priority: P1)

Somebody opens What's new and reads an entry about a new feature. Under its description stand one or more screenshots of the screen that changed, so they recognise it before they go looking for it in the application.

**Why this priority**: This is the feature. Without it nothing else in this specification has anything to show.

**Independent Test**: Publish one entry with two screenshots, open What's new signed in and signed out, and check both screenshots appear under that entry, in the declared order, each with its description available to a screen reader.

**Acceptance Scenarios**:

1. **Given** an entry that declares two screenshots, **When** somebody opens What's new, **Then** the two screenshots appear under the entry's description, in the order the entry declares them.
2. **Given** an entry that declares one screenshot, **When** somebody opens What's new, **Then** it stands alone at the full width of the entry, with a visible control saying it can be viewed full screen, and its description under it.
3. **Given** an entry that declares several screenshots, **When** somebody opens What's new, **Then** they stand as a grid of smaller previews, each with its description under it.
4. **Given** an entry that declares no screenshot, **When** somebody opens What's new, **Then** the entry reads exactly as it does today, with no empty space where screenshots would stand.
5. **Given** a reader using French, **When** they open What's new, **Then** each screenshot's description reaches them in French, like the entry's title and body.
6. **Given** a visitor who is not signed in, on an instance where What's new is public, **When** they open it, **Then** they see the screenshots too.

### User Story 2 - Look at a screenshot full screen (Priority: P1)

A screenshot in the list is small. The reader selects it and it opens over the page, as large as the window allows, so the detail can be read. They close it and land back where they were.

**Why this priority**: A screenshot shrunk to the width of a column is often unreadable, so without this the first story delivers little. Equal priority, shipped together.

**Independent Test**: Select a screenshot with the mouse, then with the keyboard; check it opens full screen, closes with the close control, with Escape and with a click outside the image, and that focus returns to the screenshot that was selected.

**Acceptance Scenarios**:

1. **Given** a screenshot in an entry, **When** the reader selects it with a pointer or with the keyboard, **Then** it opens over the page at the largest size the window allows without cropping it, with its description under it, and keyboard focus moves to the close control.
2. **Given** an open screenshot, **When** the reader presses Escape, selects the close control or selects the area around the image, **Then** it closes and keyboard focus returns to the screenshot they opened. Selecting the image itself does not close it.
3. **Given** an entry with several screenshots and one of them open, **When** the reader asks for the next or the previous one (control or arrow key), **Then** the next or previous screenshot of the same entry is shown, and the view says which one it is out of how many ("2 of 3"). From the last screenshot, next shows the first, and from the first, previous shows the last.
4. **Given** an open screenshot, **When** a screen reader reaches it, **Then** it announces a dialog named after the entry and the screenshot's description.

### User Story 3 - Add screenshots to an entry without guessing (Priority: P2)

Whoever writes the entry (a maintainer or an agent following the changelog skill) adds screenshots whenever the change can be seen, by capturing the part of the screen that changed, placing WebP files in the public changelog folder and declaring them in the entry. If they get it wrong, the existing changelog check says exactly what is wrong before the change is merged.

**Why this priority**: The readers' stories depend on entries being written correctly, and the folder will grow for years. A rule nobody checks drifts.

**Independent Test**: Run the changelog check on a set of entries that each break one rule below and confirm each one is reported by name, then on a correct set and confirm nothing is reported.

**Acceptance Scenarios**:

1. **Given** an entry declaring a screenshot whose file is missing, **When** the check runs, **Then** it reports the entry and the missing file.
2. **Given** a file in the public changelog folder that no entry declares, **When** the check runs, **Then** it reports the orphaned file.
3. **Given** a declared screenshot that is not WebP, or a screenshot declared without a description, **When** the check runs, **Then** it reports it.
4. **Given** the screenshots of one entry, **When** somebody lists the public changelog folder, **Then** the files of that entry sit together and are found by searching for the entry's id.
5. **Given** an agent writing the entry of a new feature spread over three screens, **When** it follows the changelog skill, **Then** it captures, with Iris, only the screen that shows the most value, framed on the part that changed, and not the confirmation screen at the end.

### Edge Cases

- An entry declares screenshot 1 and 3 but not 2: the check refuses the gap, so the order cannot be ambiguous.
- A screenshot is very tall (a whole page) or very wide: the preview and full screen both show it whole, keeping its proportions, scaled down to fit and never cropped or enlarged.
- A screenshot fails to load in the reader's browser: the description is shown in its place and the rest of the entry still reads.
- On a phone: the screenshots of an entry fit the width of the screen without horizontal scrolling of the page, and full screen uses the whole screen.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: An entry MUST be able to declare zero or more screenshots, up to six.
- **FR-002**: Each declared screenshot MUST carry a description, written in English, that says what the screenshot shows. The description is a translatable string like the entry's title and body, and the translation check MUST require it in every locale.
- **FR-003**: The order of an entry's screenshots MUST be the order it declares them, numbered from one without a gap.
- **FR-004**: The declaration MUST live in the entry's front matter and MUST respect the existing front matter format: flat `key: value` lines, one line per value. No list syntax or nested structure is introduced.
- **FR-005**: A screenshot file MUST be stored in WebP format in a `changelog` folder of the public directory.
- **FR-006**: A screenshot file's name MUST be derived from the entry's id and the screenshot's position, so that the entry's id alone finds its files. The name MUST NOT depend on the entry's file name, which can change, nor on its slug.
- **FR-007**: What's new MUST show an entry's screenshots under its description, for every reader who can see the entry, signed in or not.
- **FR-008**: Each screenshot MUST show its translated description as a visible caption under it, in the entry and in full screen, and MUST expose it as its text alternative. A screen reader MUST hear the description once per screenshot, not once for the image, again for the caption and again for the control.
- **FR-009**: Selecting a screenshot, with a pointer or the keyboard, MUST open it full screen over the page, scaled to fit the window without cropping.
- **FR-010**: The full screen view MUST close on Escape, on its close control and on a selection outside the image, and MUST return focus to the screenshot it was opened from.
- **FR-011**: While open, the full screen view MUST keep keyboard focus inside itself, MUST be announced as a dialog, and MUST state the position of the screenshot among the entry's screenshots when the entry has more than one.
- **FR-012**: When an entry has several screenshots, the full screen view MUST let the reader move to the previous and next screenshot of the same entry, with controls and with the arrow keys, wrapping from the last to the first and back. It MUST NOT move into another entry's screenshots. With one screenshot, it MUST show neither control.
- **FR-013**: The full screen view MUST NOT animate for a reader who asked for reduced motion.
- **FR-014**: The changelog check MUST refuse: a declared screenshot with no file, a file in the folder that no entry declares, a file that is not a valid WebP image, a declaration with no description, a gap in the numbering, more than six screenshots, and a file heavier than 1 MB.
- **FR-015**: The API representation of a changelog entry MUST list its screenshots, each with its full address and its translated description, in order. An entry without screenshots MUST list none.
- **FR-016**: The changelog skill MUST document how to declare a screenshot, name its file and produce a WebP file, and MUST make screenshots the default for an entry about a visible change (a new screen, a new or moved control, a changed layout) wherever it can be shown. An entry with nothing visible carries none.
- **FR-017**: The screenshots MUST follow the design reference `Changelog.dc.html`, read on 2026-11-25:
  - **One screenshot**: a preview at the natural size of the image, shrunk to the entry's text column when wider, with a "View full screen" label inside its lower right corner and the caption under it.
  - **Several screenshots**: a grid of previews, as many per row as fit at a minimum of 180px each, each keeping its proportions, with its caption under it.
  - **Preview**: the whole image, never cropped or enlarged (a change from the design's 16:9 and 4:3 frames, decided on 2026-11-25 after a trial); the frame is drawn as a panel (hairline, 8px corners, panel shadow, the card tint behind the image while it loads) and the pointer shows a zoom cursor. It sits between the body and the feedback controls of the entry.
  - **Full screen**: a near-black backdrop covering the window in both themes; along the top, the position ("2 of 3", or "Screenshot" alone when there is one) and a Close button; the image in the middle, 8px corners and the pop shadow; the previous and next buttons on either side of it; the caption centred under it. The page behind does not scroll while it is open.
- **FR-018**: The skill MUST require screenshots to be taken with Iris, as the `take-screenshot` skill says, framed and zoomed on the part that changed with enough around it to recognise where it is, never the whole window with the top bar and the sidebar unless the change is in them.
- **FR-019**: The skill MUST ask for one screenshot unless several each show something the others do not. For a change spread over several screens or steps, only the step that shows the most value is captured, never a confirmation or congratulation screen, unless the request asks for more.
- **FR-020**: The `take-screenshot` skill MUST allow a screenshot to be committed under `public/changelog`, as it already allows for the product documentation.

### Key Entities

- **Changelog entry**: one Markdown file in `changelog/`, identified by its id (a ULID that never changes). Gains zero to six screenshot declarations.
- **Screenshot**: belongs to exactly one entry. Has a position (1 to 6), a description (English source, translated per locale) and one WebP file in the public changelog folder whose name is the entry's id plus the position.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Adding a screenshot to an entry takes one file placed in one folder and one line written in the entry, and nothing else (no migration, no command to run beyond the existing translation step).
- **SC-002**: 100% of the rule breaches listed in FR-014 are reported by the existing changelog check, each naming the entry or the file concerned.
- **SC-003**: A reader can open, browse and close the screenshots of an entry using only the keyboard, and with a screen reader hears each screenshot's description.
- **SC-004**: An entry with screenshots adds no more than 1 MB per screenshot to the page the reader downloads, and the screenshots below the first screen of the page are not fetched until the reader scrolls near them.

## Assumptions

- **Front matter, challenged and kept, with one change.** The front matter is the right place: it is already where an entry declares everything about itself, and the existing check already reads it. But it is a flat `key: value` format with no list, so the screenshots are declared as numbered keys whose value is the description (for example `screenshot_1: The contact page with the template named under the name`). The file name is not written in the front matter at all, because it is derived (FR-006). The description, not the file name, is the part that has to be written by hand, and it is the part that would otherwise be forgotten.
- **Named after the id, not after the entry's file.** The request asks for the file to match the entry's file name with its id. The id alone is kept, as `public/changelog/<id>-<position>.webp`, because the changelog skill makes the id the entry's identity and allows the file name to change; a screenshot named after the file name would break on a rename. The id is still in the entry's file name, so searching for it finds the entry and its screenshots together, which is what "retrouver facilement" asks for. Confirmed by the requester on 2026-11-25.
- Screenshots are taken from the seeded example account, in English and the light theme, and converted to WebP before committing; the application converts nothing. A translated screenshot per locale is out of scope.
- The six screenshot cap and the 1 MB weight cap are defaults chosen to keep the page light; they are not in the request.
- **What the design reference settles, and what it does not.** `Changelog.dc.html` was read on 2026-11-25 and FR-017 records what it decides. It draws the whole page, but everything around the screenshots (the day column, the badges, the area, the feedback controls) is what What's new already shows and is not changed by this feature. The mock gives each preview its own crop position, only so that one sample image can stand for several screens; that is not adopted, since it would need a second front matter key per screenshot, and every preview is cropped from the top. The mock repeats the description as the image's alternative, the caption and the control's name; FR-008 asks for it to be heard once instead.
- **Fixes.** Screenshots are allowed on every type of entry. The design shows no fix carrying one. A fix's screenshots appear in its expanded detail, as the several-screenshots grid, so the condensed list of fixes stays condensed.
- Screenshots are not shown in emails or in the "What's new" link inside the application, only on the What's new page and in the API.
