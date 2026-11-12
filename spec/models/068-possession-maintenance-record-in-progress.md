# 068. PossessionMaintenanceRecord

**Status:** in progress (`app/Models/PossessionMaintenanceRecord.php`)
**Spec:** 3.2, specs/103-possession-maintenance

## What it is

One job done on one [Possession](062-possession-in-progress.md): an inspection, a cleaning, a repair, a restoration. It says what was done, by whom, when, for how much, what state the possession was in before and after, and when it should be looked at again. Each job is a record of its own, so the records together are the history of the work on that copy.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the possession
vault_id                    int                             vault of the possession
possession_id               int                             possession it belongs to
type                        string                          inspection, cleaning, servicing, repair, restoration, part_replacement, conservation or other
title                       string (255)                    what was done, required
performed_by                string (255)                    who did it, as text, optional
performed_on                date                            when it was done, not after today
cost                        decimal (12, 2)                 0 or more, optional
currency                    string (3)                      currency of the cost, required with a cost
condition_before_id         int, nullable                   a PossessionCondition of the account, before the job
condition_after_id          int, nullable                   a PossessionCondition of the account, after the job
next_due_on                 date                            when it should be done again, not before performed_on, optional
description                 text                            optional
```

## Rules

- The type comes from a fixed list, and each type has a vault colour of its own.
- The conditions are the account's [conditions](065-possession-condition-in-progress.md). Deleting a condition empties that side of the records naming it.
- Records read newest performed first, the latest recorded first on the same day.
- Only the latest record can be overdue: when its next due date is before today, in the person's timezone. A later job supersedes the schedule of an earlier one. Nothing about it is stored.
- The cost is never converted and never counted in the transactions or the declared value.
- Records are reached from the possession page, through the API (`/api/v1/vaults/{vault}/possessions/{possession}/maintenance-records`) and through the MCP server. Adding, changing and deleting one is written to the changelog.
- Deleting the possession deletes its records.
