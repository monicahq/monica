# 065. PossessionCondition

**Status:** in progress (`app/Models/PossessionCondition.php`)
**Spec:** 3.2, specs/093-possession-conditions, specs/103-possession-maintenance

## What it is

The state a [possession](062-possession-in-progress.md) is in: new, used, broken. Conditions belong to the [Account](001-account-in-progress.md), shared by all its vaults, are kept in an order running from best to worst, and are managed from the account settings.

Possessions cannot carry a condition yet. [Maintenance records](068-possession-maintenance-record-in-progress.md) name one before and one after the job.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account the condition belongs to
name                        string (50)                     what it reads, required
colour                      string (7)                      one of the vault colours
icon                        string (30), nullable           one of the glyphs of the section catalogue
position                    int                             place in the account's order, from 1
```

## Rules

- A name is 1 to 50 characters once markup and surrounding blank space are stripped, and unique in the account ignoring case. Changing only the case of a condition's own name is allowed.
- A condition added without a colour takes the first vault colour; without an icon it has none.
- A new condition goes last. Moving one renumbers the others; deleting one closes the gap.
- Every account starts with six, in the language of its person: New, Like new, Used, Worn, Damaged, Broken. An account that held none when the feature shipped received them once.
- Deleting a condition is permanent, after a confirmation naming it and saying how many maintenance records name it. Those records then read "Not recorded" on that side.
- Conditions are managed in the account settings, through the API (`/api/v1/possession-conditions`) and through the MCP server. Adding, changing, moving and deleting one is written to the changelog, as is writing the six defaults.
