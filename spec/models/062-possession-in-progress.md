# 062. Possession

**Status:** in progress (`app/Models/Possession.php`)
**Spec:** 3.2, specs/091-possessions, specs/094-add-possession-screen, specs/099-possession-transactions, specs/101-possession-detail-tags, specs/102-possession-insurance, specs/103-possession-maintenance, specs/115-possession-valuations, specs/116-possession-provenance, specs/117-possession-locations, specs/118-possession-loans

## What it is

Something the account owns, recorded in one [Vault](007-vault-in-progress.md): a car, a bicycle, a mug. It is of one [PossessionType](059-possession-type-in-progress.md), sits at the [Location](058-location-in-progress.md) of its open [stay](076-possession-location-stay-in-progress.md) if it has one, holds one [value](063-possession-field-value-in-progress.md) for each field of its type that somebody filled in, records what it would cost to replace, carries any of the account's [tags](064-possession-tag-in-progress.md), holds its [transactions](066-possession-transaction-in-progress.md), holds its [insurance records](067-possession-insurance-record-in-progress.md), holds its [maintenance records](068-possession-maintenance-record-in-progress.md), holds its [valuations](074-possession-valuation-in-progress.md), holds its [provenance events](075-possession-provenance-event-in-progress.md), holds its [location stays](076-possession-location-stay-in-progress.md), and holds its [loans](077-possession-loan-in-progress.md).

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the vault
vault_id                    int                             vault it is recorded in
possession_type_id          int                             its type, same account, never changes
current_location_id         int                             the location of its open stay, null without one
name                        string (255)                    what it is called, required
description                 text                            up to 2,000 characters, optional
identifier                  string (255)                    serial number, VIN, frame number, optional
status                      string                          owned, ordered, lent, sold, given_away, lost, stolen or thrown_away
quantity                    int                             1 to 1,000,000, 1 by default
is_featured                 bool                            shown among the main possessions
declared_value              decimal (12, 2)                 what it would cost to replace today, 0 or more
declared_value_currency     string (3)                      currency of the declared value, set only with one
position                    int                             order among the featured possessions of the vault, from 1; null otherwise
```

## Rules

- A new possession is owned, counts one, and is not featured.
- Featured possessions are ordered without a gap within their vault; a possession leaving the order, or deleted, closes the gap.
- Deleting it deletes its values, its transactions, its insurance records, its maintenance records, its valuations, its provenance events, its location stays and its loans for good, and detaches their documents. Deleting its type deletes it and its values; a location any of its stays refers to cannot be deleted.
- How, when and from whom it was acquired is read from its acquisition transaction, the earliest purchase, exchange, gift received or inheritance, undated ones first. It is never stored on the possession. Until specs/099 it was, in three columns, and each recorded acquisition became one transaction.
- Tags are given by name: a name matching a tag of the account, ignoring case, reuses it, and any other name creates a tag.
- On the possession's page, tags are put on, created and taken off one at a time. Taking one off asks first and never deletes the tag from the account.
- Possessions are added from the Add a possession screen of a vault, and reached through the API (`/api/v1/vaults/{vault}/possessions`) and the MCP server. Creating, changing and deleting one is written to the changelog.
