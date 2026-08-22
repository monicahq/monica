# Phase 1 Data Model: Correct Plural Phrasing in Interface Copy

Nothing here is persisted. No migration, no table, no column, no model. The feature's data is the
phrase catalogue: nine JSON files and the phrases written in code. This document describes the shapes
those take, since they are what the check validates and what the tests assert against.

## Counted phrase

A piece of interface copy whose wording depends on a number shown inside it.

| Attribute | Value |
|---|---|
| Identity | The full pipe separated English sentence, which is both the key and the English source |
| Segments | One per plural category of the language, separated by `\|` |
| Placeholder | `:count`, present in every segment, supplied by `trans_choice` and never passed by hand |
| Requested through | `trans_choice()`. Never `__()`, `trans()` or `@lang` |
| Lives in | `lang/en.json` as key and value; `lang/<locale>.json` as key and translated value |

Example, as it appears in code and in each file:

```php
trans_choice(':count person|:count people', $count)
```

```json
"en":    ":count person|:count people"
"fr_FR": ":count personne|:count personnes"
"tr_TR": ":count kişi"
```

**Validation rules**, each enforced by the check and each traceable to a requirement:

| Rule | Requirement |
|---|---|
| A phrase whose text contains `:count` is requested through `trans_choice` | FR-015 |
| Every segment contains `:count`; the digit is never written literally | FR-002, FR-006 |
| The segment count matches what the locale requires, no fewer and no more | FR-009, FR-016 |
| A phrase carries all its segments in one entry, never split across keys | FR-003 |
| No wording is selected by comparing a count to one | FR-004, FR-015 |

## Number independent phrase

A phrase that shows a number whose wording does not change with it.

| Attribute | Value |
|---|---|
| Identity | The English sentence, prefixed `[0,*]` |
| Segments | Exactly one, for every locale including those needing two |
| Requested through | `trans_choice()`, which strips the prefix. `__()` would print it to the reader |
| Declaration | The `[0,*]` prefix itself. Absence of a prefix is not a declaration |

```php
trans_choice('[0,*]:count of :total shown.', $count, ['total' => $total])
```

**Validation rules**: the prefix must be `[0,*]` exactly, and the phrase must carry one segment in
every locale. A reviewer lists every such phrase with `grep '\[0,\*\]' lang/en.json`, which is how
SC-010 is met.

## Plural category set

Not stored. Derived at check time by probing `MessageSelector::getPluralIndex()` for each configured
locale, per research decision D3. The value is the highest index returned plus one.

| Locale | Segments required |
|---|---|
| en, de_DE, es_ES, fr_FR, it_IT, nl_NL, pt_BR, pt_PT | 2 |
| tr_TR | 1 |

French requires two but selects segment 0 at a count of zero as well as one, which is why every
segment must interpolate `:count`.

## Empty state wording

Copy shown when there is nothing to count. Not a counted phrase, not touched by this feature, and
never replaced by a counted phrase given zero. `VaultsViewModel::headcount` is the pattern to
preserve: zero returns "Nobody in it yet", and only one and upwards reach the counted phrase.

## Relationships

```text
Configured locale  1 ── n  translated phrase entry
                   │
                   └── determines ── segments required (probed, not stored)

Counted phrase  1 ── n  segments (one per plural category)
                │
                └── referenced by ── n call sites (69 today, across 31 files)

Number independent phrase  1 ── 1  segment, in every locale
```

## What leaves the catalogue

| Leaving | Count | Where its wording goes |
|---|---|---|
| Standalone singular keys (`1 person`, `1 template`, ...) | 18 | Into segment 0 of the counted phrase, with `1` replaced by `:count` |
| Fixed plural keys (`:count people`, `Opened :count times`, ...) | up to 46 | Into the counted phrase, joined to the singular by `\|` |

Each departure is reported by `monica:localize` under "Removed, no longer found in the code", and takes
its eight translated values with it, which is why FR-014 requires the replacements translated in the
same change.
