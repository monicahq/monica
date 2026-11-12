# Quickstart: MCP Server

How to prove the feature works, by hand and by suite. The capability list is in
[contracts/capabilities.md](contracts/capabilities.md); nothing is repeated here.

## Prerequisites

```bash
composer require laravel/mcp
php artisan migrate:fresh
```

`client` is added to `personal_access_tokens` by altering the migration that creates the table, which
is what `.claude/skills/migrations/SKILL.md` requires until the product is in production. An edited
`Schema::create` never reaches a database that already ran it, so rebuilding is the only way to pick
the column up. No other table changes shape.

## Prove it by hand

1. **Mint a key for an assistant.** Settings, Security, API keys. Give it a name and choose
   *Assistant* rather than *Personal script*. Copy the key; it is shown once.
2. **Check the key list says so.** The row reads as an assistant's key. That is User Story 3.
3. **Point the inspector at it.**

```bash
php artisan mcp:inspector
```

   Choose the `monica` server, supply the key as a bearer token, and list tools. Thirteen appear.
4. **Read.** Call `list-vaults`. You get the account's vaults and no other account's. Call
   `get-me-contact` with one of them.
5. **Write.** Call `create-contact` with a vault and a name. Open the contact in the browser: it is
   an ordinary contact with no marker on it.
6. **Read the changelog.** Settings, then the account's log. The entry for that contact names the key
   and says the change arrived over MCP, not over the web.
7. **Cut it off.** Revoke the key, then call any tool again. It is refused.

## Prove it by suite

```bash
php artisan test --compact tests/Feature/Mcp tests/Unit/Actions/CreateApiKeyTest.php
```

What each guard is for:

| Test | What fails if it breaks |
| --- | --- |
| `McpCatalogueTest` | SC-003. Every tool names a route `Route::has()` knows |
| `McpAuthenticationTest` | FR-002. No key, a revoked key and an expired key are each refused |
| `McpCompartmentTest` | SC-004. Another account's vault and contact answer not found, per capability |
| `McpChangelogTest` | FR-008, SC-005. A write lands with source `mcp` and the key's name |
| `McpRateLimitTest` | FR-010, SC-006. A limit on hosted, none when `monica.self_hosted` is true |
| `McpRefusalLanguageTest` | FR-011. A refusal comes back translated, not in English |

The whole suite must pass on all three connections before merge:

```bash
php artisan test --compact
```

## Where it is documented

`resources/docs/api/19-mcp.php`, a guide page in the portal beside the API reference. FR-012.
