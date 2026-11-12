# 053. ModuleInstallation

**Status:** to create, foundations only
**Spec:** 3.9, 3.3, 12

## What it is

What one account did with one module: installed it, configured it, granted it permissions, turned it off.

It is separate from [Module](052-module-not-yet.md) because the module is instance level and the decision to run it is per account. On a self hosted instance with one account the distinction looks academic. On the hosted version it is not, and the same code has to serve both (3.4).

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API
account_id                  int                             account that installed it
module_id                   int                             module installed
version                     string (32)                     version currently running here
settings                    json                            configuration the user gave it
granted_permissions         json                            what the user actually allowed, which may be less than what was asked
is_enabled                  boolean                         whether it runs, so it can be turned off without being removed
disabled_reason             enum (user, incompatible, failed, revoked)  why it stopped, so it is explained rather than gone
disabled_at                 datetime                        when it stopped
installed_by_user_id        int                             user who installed it
installed_at                datetime                        when it was installed
last_error_at               datetime                        when it last failed
last_error_message          string (512)                    what the failure said, so it can be diagnosed without our help
update_available_version    string (32)                     newer version available, null when it is current
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Module](052-module-not-yet.md), [User](002-user-done.md)

## Rules

- Permissions are granted explicitly and can be less than what the module asked for. A module that cannot run with what it was given says so and stays disabled, rather than reaching for more.
- A module that becomes incompatible with the running version of Monica is disabled with a reason, not silently skipped (3.3).
- Repeated failures disable it, with the last error kept so the user can see what happened. Same pattern as a webhook subscription (14.4), and for the same reason: an extension that fails quietly is worse than one that fails loudly.
- Uninstalling never destroys user data by itself. What happens to data a module created depends on the open question in [Module](052-module-not-yet.md) about whether modules may own tables, and until that is answered nothing may be deleted on uninstall.
- Everything a module does is attributed to it in [Log](004-log-in-progress.md), through `source` and `source_detail` (12).
- **A module update never applies itself.** The same rule as the marketplace and for the same reason (3.1): the account is told, and decides.

## Open questions

All of the ones in [Module](052-module-not-yet.md), plus:

- Whether a module can be confined to a vault the way a webhook subscription or an API token can. It would be consistent with everything else in the product, and it is much harder to enforce against arbitrary code than against a fixed API surface.
