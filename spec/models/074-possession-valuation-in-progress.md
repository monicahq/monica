# 074. PossessionValuation

**Status:** in progress (`app/Models/PossessionValuation.php`)
**Spec:** 3.2, specs/115-possession-valuations

## What it is

One estimate of what one [Possession](062-possession-in-progress.md) is worth on one date: an own estimate, an appraisal, an insurance declaration, an auction result, a comparable listing or an offer received. A valuation is never edited once recorded. To correct a figure, somebody records a new one, so the earlier ones stay as the history of how the value moved.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the possession
vault_id                    int                             vault of the possession
possession_id               int                             possession it belongs to
type                        string                          own_estimate, appraisal, insurance_declaration, auction_result, comparable_listing or offer_received
valued_on                   date                            the day the estimate applies to
confidence                  string                          unknown, certain, probable or uncertain; unknown by default
amount                      decimal (12, 2)                 0 or more
currency                    string (3)                      currency of the amount
valuer                      string (255)                    who estimated it, as text, optional
method                      string (255)                    how it was estimated, optional
reference_number            string (255)                    optional
source_url                  string (255)                    a valid URL, optional
note                        text                            optional
```

Documents are attached through `possession_valuation_file`, one row per [File](008-file-in-progress.md).

## Rules

- The current valuation is the one with the latest date; on the same date, the latest recorded wins.
- The possession's current value is its current valuation, and its declared value when it has none.
- The change from one valuation to the one before it is worked out each time, only when both are in the same currency. Nothing is converted.
- Valuations are reached from the possession page, through the API (`/api/v1/vaults/{vault}/possessions/{possession}/valuations`) and through the MCP server. Recording and deleting one, and attaching and detaching a document, are written to the changelog.
- Deleting a valuation detaches its documents, which wait out the grace period every detached file gets.
- Deleting the possession deletes its valuations.
