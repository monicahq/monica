# Research: Notes in a repeater

## R1. Which field reads as notes

**Decision**: a repeater whose children that store a value are exactly one, and that one is a Long
text (`FieldTypeEnum::Textarea`). Decided in `ContactViewModel`, which already loads the children.

**Rationale**: matches the scope the user chose and the default **Notes** repeater, and leaves a
repeater mixing a Long text with other fields on today’s card per row (spec edge case).

## R2. What the view model hands the screen

**Decision**: each entry of `fieldsIn()` gains `notes`: `null` for any other field, else
`{showsLabel: bool, entries: list<{id, shown: list<string>, hidden: list<string>, addedLine: string}>}`.
`showsLabel` is false when the field label equals the section name. Rows whose text is blank are
left out, so an empty `entries` is the empty state.

**Rationale**: the view models skill keeps grouping, labels and empty states in the view model, where
the rule is tested once. The Blade only loops.

## R3. Paragraphs and the preview

**Decision**: the text is split on blank lines into paragraphs, each kept with its single line breaks.
The first two paragraphs are `shown`, the rest `hidden`. A note of two paragraphs or fewer has no
control.

**Rationale**: frame 1b collapses after the second paragraph. Counting paragraphs is exact in every
language, where a character count cuts mid-sentence and depends on the script.

**Limitation**: one very long paragraph never collapses; a clamp by lines would need the browser.

## R4. Read more without breaking the page without scripts

**Decision**: the hidden paragraphs are rendered in a block with an `id`, `x-show="expanded"` and no
`x-cloak`. The button carries `x-cloak`, `:aria-expanded`, `aria-controls` pointing at that block,
and swaps its wording between **Read more** and **Show less** with `x-text`.

**Rationale**: without scripts the whole note is visible and the button never appears (spec US2-4).
With scripts Alpine hides the rest on start. A native `<details>` was rejected: its wording cannot
change without a script, and closed it hides the text that should be readable.

## R5. Markup inside the detail list

**Decision**: the note block is one `<div>` in the section’s `<dl>`, holding a `<dt>` (visible heading
when `showsLabel`, `sr-only` otherwise) and a `<dd>` with the notes. It spans the full width, padded
`px-6 py-5` as drawn, with the same bottom rule as a row.

**Rationale**: a `<div>` of `<dt>` and `<dd>` is valid in a `<dl>`, so the section stays one list and
the label is still announced when it is hidden from sight (principle IX).

**Alternatives**: closing the list around the note (splits one section into several lists); an `h3`
as drawn (a heading inside a `dl` item is invalid).

## R6. Where the markup lives

**Decision**: a partial `resources/views/app/vaults/contacts/_notes.blade.php`, included from
`_section.blade.php` with every variable named.

**Rationale**: one caller; the components rule extracts at the second file at the earliest.

## R7. The date and its words

**Decision**: `addedLine` is `__('Added :date', ...)` with the row’s `created_at` in the user’s time
zone, written by `DateFormatEnum::write()` with the user’s date format, falling back to the
locale’s, as `ContactTimelineBlock` does.

New strings: `Added :date`, `No note yet`, `Add a note`, `Read more`, `Show less`. None exists in
`lang/`. None shows a count.

## R8. The look

**Decision**: note text `text-[14.5px]/[1.7] text-ink text-pretty max-w-[68ch]`, paragraphs `gap-3`;
the date `text-xs text-muted`; the heading `text-sm font-semibold text-ink-soft`. Read more is a text
button with `focus-ring`; on mobile a full width secondary button of 44px (frame 1f). Add a note is
`x-button.secondary size="small"` with the `plus` icon, its `href` the section `editUrl` and the same
`x-target`, like **Edit**.

**Rationale**: 14.5/1.7 is the design’s reading size and has no token (the design system’s own
`typography.css` stops at `--text-base` 13.5). A one off arbitrary value beats a token with one user.
