# Quickstart: Template version history

How to prove the feature works end to end. Requirements are in [spec.md](spec.md), decisions in [research.md](research.md).

## Prerequisites

Laravel Herd serving `http://erica.test`, the database migrated and seeded, and a signed-in account. Front-end assets built with `bun run dev` or `bun run build`.

## Set up a template with a history

Use the account's supplied **Basic contact** template. In the builder at
`http://erica.test/settings/account/templates/basic-contact/builder`:

1. Publish the current version if it is still a draft.
2. Create a new version, add a section and a field to it, and publish it.
3. Create a third version and leave it as a draft.

The template now has a published v1, a published v2 and a v3 draft, which is the state the design draws.

## Walk the feature

| Step | Expected |
| --- | --- |
| Open the builder | A **Version history** link sits under the title, after `Saved …` and `Edit` |
| Follow it | The history screen opens, titled after the template, with v3, v2 and v1 newest first |
| Read v2's entry | Its status, publication date, `based on v1`, its change note, its page/section/field counts, and what it added over v1 |
| Expand v2 | Its pages appear in order; each lists its sections with the section version placed and a field count |
| Expand a section added in v2 | It is marked as new in this version, and its fields are listed with their type |
| Read v1's entry | It says it is where the template starts, with no "added" summary |
| Follow the back control | The builder reopens on the same template |

## Prove it is frozen and compartmented

- Note what v1's entry shows. Edit the v3 draft, add a field, and reopen the history: v1's entry is unchanged.
- Sign in as another account and request the same URL: the response is 404, from `ResolvesModelInAccount`.
- Open the builder of a template that has one version only: no link.

## Accessibility pass

- Reach every version and section expander with `Tab` alone and open it with `Enter` or `Space`; the focus ring is visible throughout.
- Confirm each expander reports `aria-expanded` correctly as it opens and closes.
- Zoom to 200% and confirm nothing is clipped and nothing scrolls sideways.

## Automated checks

```bash
php artisan test --compact --filter=TemplateVersionHistory
php artisan test --compact tests/Unit/ViewModels/Settings/Account/Templates
php artisan monica:localize --check
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=2G
```

`monica:localize --check` must pass in all nine locales, and PHPStan runs at level 5 in CI.
