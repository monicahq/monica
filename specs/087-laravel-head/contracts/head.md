# Contract: what each kind of page carries in its head

The crawler and the link preview read only these tags. The tests in [quickstart.md](../quickstart.md)
assert them.

## Robots, canonical and preview, by kind of page

| Page | `robots` | canonical | `og:*` |
| --- | --- | --- | --- |
| Signed in screen, settings, admin, sign in screens | `noindex` | none | site name, type, title |
| Private link and its password form (`share.*`) | `noindex, nofollow` | none | site name, type, title |
| Any error page, wherever it answers | `noindex` | none | site name, type, title |
| Docs, API reference, CardDAV, MCP, guide, marketplace | `all` | the page's own address | site name, type, title, description |
| The same public pages with `MONICA_SEARCH_ENGINE_INDEXING=false` | `noindex` | none | site name, type, title, description |

- The canonical address is on the instance's own host, without the query string, and keeps the
  scheme the request arrived on.
- `og:title` and `og:description` repeat the document title and description.
- A private link's title stays the contact's name.

## Unchanged

- `<title>`: the same wording as today on every screen, the application name when none is set.
- `charset`, `viewport`, `csrf-token` (only with a session), `turbo-cache-control`, the icons, the
  manifest and the theme script stay as `partials/meta` writes them now.
- The `.md` addresses, `llms.txt` and the OpenAPI document carry no head and do not change.

## Guide front matter

Every file under `docs/portal/{locale}` gains a required `description`, one sentence in the page's
language:

```yaml
---
id: security.password
title: Change, set or remove your password
description: How to change your password, set one after signing up another way, or remove it.
slug: your-password
section: your-account-and-security
last_updated: 2026-11-04
---
```

A page without one is left out of the catalogue, the way a page without a `title` is today.
