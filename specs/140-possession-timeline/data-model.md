# Data model: Possession timeline

Nothing is stored. The timeline is computed from seven existing tables each time it is read.

## PossessionTimelineEntry (value object)

| Property | Type | Meaning |
| --- | --- | --- |
| `section` | `PossessionTimelineSectionEnum` | Transactions, Insurance, Valuations, Provenance, Maintenance, Loans or Locations |
| `kind` | string | What happened: `transaction`, `insurance`, `valuation`, `provenance`, `maintenance`, `loan_started`, `loan_returned`, `stay` |
| `record` | the section's model | The record the entry is read from |
| `date` | `CarbonImmutable|null` | When it happened; null when unknown |
| `precision` | `PossessionProvenanceDatePrecisionEnum` | `Day` for every section but provenance, which keeps its own |
| `isCurrent` | bool | The latest valuation, or the stay without a departure |
| `isAcquisition` | bool | A purchase, gift received, inheritance or exchange |

## PossessionTimelineSectionEnum

| Case | Key | Icon | Colour | Section page |
| --- | --- | --- | --- | --- |
| Transactions | `transactions` | coin | Green | `possessions.transactions.index` |
| Insurance | `insurance` | lock | Rust | `possessions.insuranceRecords.index` |
| Valuations | `valuations` | activity | Blue | `possessions.valuations.index` |
| Provenance | `provenance` | family | Violet | `possessions.provenance.index` |
| Maintenance | `maintenance` | settings | Amber | `possessions.maintenanceRecords.index` |
| Loans | `loans` | relationship | Plum | `possessions.loans.index` |
| Locations | `locations` | place | Teal | `possessions.locationStays.index` |

Icons and colours are those `PossessionViewModel::sections()` already gives each section.

## Rules

1. One entry per record, two for a returned loan, none for a planned loan (research R4).
2. A provenance event written by a loan or tied to a transaction gives no entry (R3).
3. Order: date descending, undated last, ties by the record's creation descending.
4. Groups on the screen: the year of the date; undated entries form **Date unknown**, last.
5. Filter: an optional section key; counts on the chips are taken before filtering.
