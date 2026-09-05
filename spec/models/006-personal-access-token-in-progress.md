# 006. PersonalAccessToken

**Status:** exists as a table, provided by Sanctum, changes needed
**Spec:** 3.6, 14.5

## What it is

The key an external caller authenticates with. Section 3.6 makes this load bearing: everything doable in the interface must be doable through the API, the mobile applications will consume that API, and the official MCP server authenticates the same way and never bypasses it.

Version one of the API exists (`routes/api.php`, prefix `v1`), it authenticates with these keys through `auth:sanctum`, and `api-keys` lists, shows, mints and destroys them. What has not moved is the row: `abilities` is still written and never read, and none of the four changes listed below has been made. So there is an API to scope and nothing scoped to it, which is what this file describes closing.

## Fields

```
id                          int                             primary key
tokenable_type              string (255)                    class the key acts as, which is always the user today
tokenable_id                int                             id of the model the key acts as
name                        string (255)                    name the person gave the key, so they can tell one from another
token                       string (64)                     hash of the key, since the key itself is shown once and never stored
abilities                   text                            scopes the key is allowed to use
vault_id                    int                             vault the key is confined to, null when it reaches the whole account
client                      enum (api, mcp, mobile, module)  what kind of caller the key was issued for
last_used_at                datetime                        when the key last authenticated a request
expires_at                  datetime                        when the key stops working, null when it never does
revoked_at                  datetime                        when the key was revoked by hand
created_at                  datetime
updated_at                  datetime
```

## What changes

- `abilities` starts being enforced. Section 3.6 requires scopes that can be narrowed, so a key handed to a script that only reads contacts cannot publish to the marketplace.
- Add `vault_id`. Vaults are the compartment (4.2), and a key should be confinable to one the same way a webhook subscription is (14.3).
- Add `client`, so the MCP server and a mobile application are distinguishable from a personal script in the log and on the settings screen.
- Add `revoked_at`, so a key can be killed without being deleted and the log still resolves what acted.

## Relationships

- `morphTo` tokenable, which is always a [User](002-user-done.md) today
- `belongsTo` [Vault](007-vault-in-progress.md)

## Rules

- The key is shown once at creation and never again. Only its hash is stored.
- Every request authenticated by a key writes its identity into [Log](004-log-in-progress.md) as `source_detail`, so the log can say which integration made a change (12).
- A revoked or expired key fails closed. It does not degrade to a read only key.
- The MCP server applies the same scopes and the same vault confinement as the API. It has no privileged path (3.6).
- Rate limits apply per key on the hosted version, and not at all when self hosted (3.6, 14.5). The limit itself is configuration, not a column.

## Open questions

- The exact list of scopes. It should be derived from the API surface rather than invented, which means it is settled when the API routes are, not before.
