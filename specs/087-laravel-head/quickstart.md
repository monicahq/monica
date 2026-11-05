# Quickstart: validating head metadata

The expected tags for each page are in [contracts/head.md](contracts/head.md).

## Automated

```bash
php artisan test --compact tests/Feature/HeadMetadataTest.php
```

```bash
php artisan test --compact tests/Unit/ViewModels/Docs
```

`HeadMetadataTest` walks one route per kind of page through a data provider and asserts its robots
instruction, then switches `monica.search_engine_indexing` off and asserts the public pages turn
noindex. The catalogue tests prove the guide reads `description` and leaves out a page without one.

## By hand

1. `composer run dev`, then open these and view the source of each:
   - `/docs/api/vaults-index?utm_source=x`: `robots` is `all`, and the canonical has no query string
   - `/docs/en`: `robots` is `all`, with a description from the front matter
   - `/vaults`, signed in: `robots` is `noindex`
   - a private link from a contact's sharing screen: `noindex, nofollow`, title is the contact's name
   - `/docs/api/does-not-exist`: `robots` is `noindex`, and there is no canonical
2. Set `MONICA_SEARCH_ENGINE_INDEXING=false`, run `php artisan config:clear`, and open `/docs/en`
   again: `robots` is `noindex` and there is no canonical.
3. Paste a guide address into a chat application that shows previews: the title and description
   appear.

## Before the pull request

```bash
php artisan monica:localize --check
```

```bash
vendor/bin/phpstan analyse --no-progress
```

```bash
vendor/bin/pint --dirty --format agent
```
