# 067. PossessionInsuranceRecord

**Status:** in progress (`app/Models/PossessionInsuranceRecord.php`)
**Spec:** 3.2, specs/102-possession-insurance

## What it is

One policy period covering one [Possession](062-possession-in-progress.md): who insures it, for how much, and until when. When a policy renews with a new insured value, or the insurer changes, a new record is added, and the earlier ones stay as the history of the coverage.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the possession
vault_id                    int                             vault of the possession
possession_id               int                             possession it belongs to
insurer                     string (255)                    who insures it, as text
status                      string                          active, pending, expired or cancelled
policy_number               string (255)                    optional
coverage                    string (255)                    what the policy covers, optional
insured_value               decimal (12, 2)                 0 or more
excess                      decimal (12, 2)                 0 or more, optional
currency                    string (3)                      currency of the insured value and the excess
starts_on                   date                            optional
ends_on                     date                            when it ends or renews, not before starts_on, optional
is_listed_individually      boolean                         named on the policy rather than under the general contents
contact_name                string (255)                    who to call at the insurer, optional
contact_email               string (255)                    a valid email, optional
contact_phone               string (50)                     optional
note                        text                            optional
```

## Rules

- The status is the one entered and never follows the dates.
- The current record is the active one with the latest start; undated ones come after dated ones, then the latest recorded wins.
- The gap between the declared value and the current insured value is worked out each time, only when both are in the same currency. Nothing is converted.
- Records are reached from the possession page, through the API (`/api/v1/vaults/{vault}/possessions/{possession}/insurance-records`) and through the MCP server. Adding, changing and deleting one is written to the changelog.
- Deleting the possession deletes its records.
