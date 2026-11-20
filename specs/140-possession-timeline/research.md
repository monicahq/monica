# Research: Possession timeline

Read from the branch `2026-11-19-possession-timeline`, stacked on `2026-11-19-relation-links`.

## R1. Where the design meets the code

- **Decision**: Reuse what the possession pages already draw. The header is
  `resources/views/app/vaults/possessions/_header.blade.php`, the column of sections is
  `_sections.blade.php`, the chips are `x-chip`, the help is `x-help`, the badges are `x-badge`. The
  page follows the layout of `valuations.blade.php`: `x-shell wide`, the header, then the
  `13.5rem / 1fr` grid with the sections on the left.
- **Rationale**: The design was drawn from those same parts (its comments name `_header.blade.php` and
  `_sections.blade.php`), so the screen already exists piece by piece. Only the entry list is new.
- **Alternatives considered**: Copying the design's inline styles, rejected by the project's Tailwind
  rule and the components rule.

## R2. One place decides what the timeline holds

- **Decision**: A class `App\Helpers\PossessionTimeline` reads the seven sections of one possession
  and returns a list of `PossessionTimelineEntry` objects (one class per file), already ordered and
  deduplicated: section, kind, the record, its date and the precision of that date. It writes no copy.
  The view model `PossessionTimelineViewModel` turns entries into what the screen reads (title,
  detail, badge, amount, date, group, link). The API controller turns the same entries into JSON.
- **Rationale**: FR-011 and SC-005 ask the screen and the API for the same entries in the same order.
  Which records make an entry, which duplicate another and in what order they come is one rule, so it
  lives once. Copy belongs to the screen, as everywhere else, and the API answers with values.
- **Alternatives considered**: All of it in the view model, rejected because the API would rebuild the
  same rule. An Action, rejected: nothing is changed, and actions are what somebody does.

## R3. Entries a section already mirrors

- **Decision**: A provenance event that a loan wrote (`departure_provenance_event_id`,
  `return_provenance_event_id` of a loan) or that is tied to a transaction (`possession_transaction_id`)
  is left out: the loan or the transaction already gives that moment.
- **Rationale**: Without it, a loan recorded in the provenance and a purchase recorded with its
  provenance each show twice.
- **Alternatives considered**: Leaving the loan or transaction out instead, rejected: their entries
  carry the amount and the person.

## R4. Which records make an entry

| Section | Entries | Date used |
| --- | --- | --- |
| Transactions | one per transaction | `occurred_on`, null when unknown |
| Insurance | one per record | `starts_on`, null when unknown |
| Valuations | one per valuation | `valued_on` |
| Provenance | one per event not mirrored (R3) | `occurred_on` with `date_precision`; `Unknown` or null is undated |
| Maintenance | one per record | `performed_on` |
| Loans | one at `started_on` for a loan whose status is not planned; one more at `returned_on` once returned | `started_on`, `returned_on`; a returned loan without a date gives an undated return |
| Locations | one per stay | `arrived_on`, null when unknown |

Order: by date, newest first; undated last; equal dates by the record's creation, newest first. The
**Acquisition** badge goes to a transaction of type purchase, gift received, inheritance or exchange.
**Current value** goes to the valuation the Valuations section already calls current. **Here now** goes
to the stay without `left_on`, as the Locations section already decides.

## R5. Wording

- **Decision**: Each entry reuses the words its section already writes: the transaction type labels,
  the insurer, the valuation type label, the provenance type label and title, the maintenance title,
  the loan direction verb and contact name, the location path, money through `PossessionValueFormatter`,
  dates through the user's date format, provenance dates through the precision wording the Provenance
  section uses ("About 2024", "March 2024"). New strings are only the timeline's own: the heading, the
  help, the lead line, the group count, "Date unknown" as a group, "Here now", "Current value",
  "Acquisition", "Returned by :name", "Lent to :name", "Borrowed from :name", "Moved to :place",
  "Kept in :place", and the amount notes.
- **Rationale**: One word for one meaning. The reader recognises an entry as the record they wrote.
- **Counted phrase**: "1 entry / :count entries" goes through `trans_choice(':count entry|:count entries', $count)`.

## R6. The filter

- **Decision**: An optional last segment on the route: `vaults/{vault}/possessions/{possession}/timeline/{section?}`,
  constrained to the seven section keys. Each chip is an `x-chip` with `href`, which already renders a
  link with `aria-current="true"` on the chosen one.
- **Rationale**: FR-006 asks the choice to survive a reload and a link; the routing rule prefers a path
  segment over a query string, as `entities.show/{page?}` does. Links work without JavaScript.
- **Alternatives considered**: Alpine filtering on the client, rejected because the choice would not
  survive a reload.

## R7. API and MCP

- **Decision**: `GET /api/v1/vaults/{vault}/possessions/{possession}/timeline` with an optional
  `section` query parameter and paging as every list; each entry gives `section`, `kind`, `date`,
  `date_precision`, the record's id and its endpoint. An MCP tool `list-possession-timeline` mirrors it.
  Documented in `resources/docs/api` and the API changelog.
- **Rationale**: Principle X. The API returns values, not the screen's sentences, as the section
  endpoints already do.
- **Alternatives considered**: No endpoint, since every record is readable from its section; rejected
  by FR-011, which the spec keeps.

## R8. The section in the column

- **Decision**: `PossessionViewModel::sections()` gives Timeline its URL and its count (entries from
  `PossessionTimeline`), on every possession page.
- **Rationale**: FR-008. The count costs one more read of the seven relations, which the sections
  already load for their own counts.

## R9. Size

- **Decision**: No paging on the screen; the timeline reads the relations already eager loaded on the
  possession. A possession with 300 records is a few hundred rows of seven small tables.
- **Rationale**: SC-004. Paging would split a year across pages, against the grouping.
