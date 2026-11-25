# Research: Screenshots in What's new

## R1. Declaration and file name

- **Decision**: `screenshot_<n>: <description>` in the front matter, `n` from 1 to 6. The file is `public/changelog/<id>-<n>.webp`, served with `asset()`. See [data-model.md](data-model.md).
- **Rationale**: the front matter stays flat (FR-004), the description is the only thing written by hand, and the id never changes (spec Assumptions).
- **Alternatives**: a list in YAML (rejected: the parser reads flat lines only); the file name in the front matter (rejected: derivable, so a second place to get wrong).

## R2. Where a screenshot lives in code

- **Decision**: a readonly `App\Helpers\ChangelogScreenshot` (`position`, `description`, `file`, `width`, `height`, `url()`), and `ChangelogEntry::$screenshots` as a list of them, last constructor argument, defaulting to `[]`.
- **Rationale**: the entry is already a readonly value object; a shape array would be rebuilt in the view model, the resource and the localize command.

## R3. The check

- **Decision**: `ChangelogEntries` takes a second optional directory, `screenshotDirectory` (defaults to `public_path('changelog')`), and `read()` validates each declared screenshot after the required fields. Each defect is one sentence naming the entry file: missing file, not WebP (`getimagesize()` type is not `IMAGETYPE_WEBP`, which also refuses an emptied or broken file), over 1 MB, empty description, a gap, more than six. `parse()` then lists files in the folder that no valid entry declares (orphans). An entry with a screenshot defect is kept, without screenshots, as a bad `link` already is.
- **Rationale**: `it_finds_no_problem_in_the_shipped_entries` already runs `problems()` over the real folder in CI, so the rules are enforced without a new command (SC-002).
- **Alternatives**: a separate Artisan check (rejected: a second place to run); dropping the whole entry (rejected: a missing image should not hide the text).

## R4. Dimensions and the cache

- **Decision**: `getimagesize()` gives width and height while parsing; they are cached with the entry. Every screenshot is a double density capture, so the image is drawn through `x-image` with `srcset="<url> 2x"` and half its pixel dimensions as `width`/`height`: it shows at its natural size, never enlarged, and keeps its proportions everywhere (decided after a trial on 2026-11-25; the design's cropped 16:9 and 4:3 frames made the screenshots unreadable). The cache signature adds the names and modification times of the files in the screenshot folder.
- **Rationale**: declared dimensions stop the page jumping as images arrive; lazy loading gives SC-004. Without the signature change, replacing an image would keep stale dimensions.

## R5. What a screen reader hears

- **Decision**: in the entry, each preview is a `<button>` holding the image with its description as `alt`, shown in its place if it fails to load; the button's name comes from `aria-labelledby` pointing at a visually hidden "View full screen" and at the caption. The visible `<figcaption>` is `aria-hidden`. In the viewer, the dialog is `aria-labelledby` the entry title plus the position, the image carries the description as `alt`, and the caption under it is `aria-hidden`.
- **Rationale**: FR-008 asks for the description once. The design's version names it three times (accepted deviation, 2026-11-25).

## R6. The viewer

- **Decision**: inside `x-screenshot-gallery`, an inline `x-data` holding the list, the open index and `move(step)` with wrapping. The viewer is teleported to `body`, `x-trap.noscroll.inert` like `x-modal.form`, which traps focus, locks the scroll and returns focus to the preview. Escape, arrow keys, the Close button and a click on the backdrop act on it; a click on the image does not. Focus starts on Close. Transitions use `duration-(--duration-standard)`, which reduced motion sets to zero (FR-013).
- **Backdrop**: three tokens added to `app.css`, identical in both themes: `--scrim-strong` (ink at 92%), `--on-scrim` (#fafafa), `--on-scrim-muted` (#e4e4e7), named after the existing `--scrim`. The existing `scrim` is 50% and would show the page through.
- **Alternatives**: a `resources/js` module (rejected: about twenty lines, one user); the native `<dialog>` element (rejected: the project's dialogs all use the Alpine focus plugin).

## R7. Translation

- **Decision**: `LocalizeCommand` adds each screenshot description to the keys it collects from entries, next to title, area and body. Interface strings: "View full screen", "Close", "Previous screenshot", "Next screenshot", ":position of :total", "Screenshot". `:position of :total` holds no `:count`, so it goes through `__()`.

## R8. The API

- **Decision**: `ChangelogEntryResource` gains `attributes.screenshots`, a list of `{position, url, description, width, height}`, empty when there is none. The docs page and a dated API changelog entry say so. No MCP tool reads the changelog, so none changes.

## R9. When an entry carries screenshots, and how they are taken

- **When**: by default for an entry about something visible (FR-016). One screenshot; several only when each shows something the others do not. Across several steps, the step that shows the most value, never the confirmation (FR-019).
- **Capture**: with Iris through a signed link (`monica:screenshot-link`, the seeded account, debug bar off), as the `take-screenshot` skill says. Frame the part that changed with `--selector '<css>' --padding 24` in a window about 720 pixels wide, so the screen wraps into a compact image. `--scale 2` always, since R4 draws every screenshot at half its pixel size (FR-018).
- **Convert**: `magick shot.png -quality 82 public/changelog/<id>-<n>.webp`, never resized, since shrinking would break the double density. Inspect the result before committing it.
- **Where the rules live**: the procedure in the `changelog` skill, a pointer to it from `take-screenshot`, which also gains `public/changelog` as its second exception to "never commit a screenshot" (FR-020).
- **Fixtures**: tiny WebP files generated once with `imagewebp()` and committed.
- **Alternatives**: a full window capture (rejected: the top bar and the sidebar dwarf the change); one screenshot per step (rejected: the reader needs the result, not the path).
