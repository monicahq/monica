# 075. PossessionProvenanceEvent

**Status:** in progress (`app/Models/PossessionProvenanceEvent.php`)
**Spec:** 3.2, specs/116-possession-provenance

## What it is

One moment in the history of one [Possession](062-possession-in-progress.md): where it was made, who acquired, inherited, received or sold it, who held it for a while, when it was lost or recovered, and what authenticated, exhibited or published it. Together the events form its provenance, read oldest first. An event holds no money: a moment that cost something points at the [transaction](066-possession-transaction-in-progress.md) that records it.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the possession
vault_id                    int                             vault of the possession
possession_id               int                             possession it belongs to
possession_transaction_id   int                             a transaction of the same possession, optional
type                        string                          origin, acquisition, inheritance, gift, sale, custody, loss, recovery, authentication, exhibition, publication or other
title                       string (255)                    optional; the type's name is shown when empty
date_precision              string                          day, month, year, circa or unknown; day by default
occurred_on                 date                            optional; the first of the month or of the year when that is all that is known
from                        string (255)                    who it came from, as text, optional
to                          string (255)                    who held it afterwards, as text, optional
location                    string (255)                    where it happened, as text, optional
description                 text                            optional
reference_number            string (255)                    optional
source_url                  string (255)                    a valid URL, optional
is_verified                 boolean                         false by default
```

## Rules

- The date is written and read in the shape its precision gives: `1962-03-14`, `1962-03`, `1962`, or nothing when unknown. A date that does not match its precision is refused.
- Events are ordered by date, oldest first, those with an unknown date last; on the same date, the earliest recorded comes first.
- Types fall in three groups: ownership (origin, acquisition, inheritance, gift, sale), custody (custody, loss, recovery) and authenticity (authentication, exhibition, publication, other).
- The origin shown for a possession is its oldest origin event. Its chain of ownership is the distinct people written in `to` on its ownership events, in order.
- Events are reached from the possession page, through the API (`/api/v1/vaults/{vault}/possessions/{possession}/provenance`) and through the MCP server. Recording, changing and deleting one are written to the changelog.
- Deleting an event leaves its transaction alone. Deleting the transaction keeps the event and clears the link.
- Deleting the possession deletes its events.
