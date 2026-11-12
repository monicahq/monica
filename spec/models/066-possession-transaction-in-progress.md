# 066. PossessionTransaction

**Status:** in progress (`app/Models/PossessionTransaction.php`)
**Spec:** 3.2, specs/099-possession-transactions

## What it is

Money or ownership changing hands for one [Possession](062-possession-in-progress.md): a purchase, a sale, an inheritance, a fee, the shipping. The possession's acquisition date, acquisition price and the costs since are read from its transactions, never copied.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the possession
vault_id                    int                             vault of the possession
possession_id               int                             possession it belongs to
type                        string                          purchase, sale, exchange, gift_received, gift_given, inheritance, refund, fee, tax, shipping or other
occurred_on                 date                            when it happened, null when not known
counterparty                string (255)                    who was on the other side, as text, optional
amount                      decimal (12, 2)                 0 or more, optional
tax                         decimal (12, 2)                 0 or more, optional
fees                        decimal (12, 2)                 0 or more, optional
shipping                    decimal (12, 2)                 0 or more, optional
entered_total               decimal (12, 2)                 0 or more, overrides the sum of the four figures, optional
currency                    string (3)                      currency of every figure, required once one is set
reference                   string (255)                    an invoice or a deed number, optional
source_url                  string (2048)                   where it can be checked, optional
note                        text                            optional
```

## Rules

- The first six types change ownership; the five others are money only. Purchase, exchange, gift received and inheritance are acquisitions.
- The total is the entered total, else the sum of the figures set, else unknown. Nothing is converted between currencies.
- The acquisition is the earliest acquisition transaction; undated ones come first, then by date, then in the order they were recorded.
- Costs since are the money only transactions dated on or after the acquisition, in its currency, refunds subtracted. Those in another currency are counted and left out.
- Recording a sale or a gift given does not change the possession's status.
- It holds any number of documents, which are [files](008-file-in-progress.md). Deleting it detaches them, so they enter the grace period.
- Transactions are reached from the possession page, through the API (`/api/v1/vaults/{vault}/possessions/{possession}/transactions`) and through the MCP server. Adding, changing and deleting one is written to the changelog.
