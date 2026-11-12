# Quickstart: API changelog

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Docs
php artisan test --compact tests/Feature/Controllers/App/Docs tests/Feature/RouteProtectionTest.php
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

All pass, PHPStan reports nothing.

## By hand

| Open | Expect |
| --- | --- |
| `/docs/api/changelog` as shipped | Title, introduction, "No change has been recorded yet.", no filters |
| Copy `tests/Fixtures/Docs/ApiChangelog/Valid/*.php` into `resources/docs/api-changelog/` | Releases newest first, Latest on the first, rows with Breaking and counts |
| Pick "Removed", tick "Breaking changes only", search a word | Rows, groups and releases hide, the count follows, no reload |
| Follow a row | Entry with every block, no "Upgrade"; documented endpoints link, removed ones are text |
| Before/After on an example with both | The shape switches; `aria-pressed` follows |
| `/docs/api/changelog.md`, `/docs/api/changelog/{entry}.md` | Markdown, no upgrade section |
| `/docs/api/changelog/unknown` | Not found |
| Sidebar on either page | "Changelog" last under Getting started, marked current |

Remove the copied fixtures afterwards.
