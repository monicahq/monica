---
name: changelog
description: Write an entry of What's new, the product changelog, for every meaningful user facing change. Use whenever you implement a feature, an improvement, a change of behaviour or a fix that people using Monica will notice, and before you open the pull request that carries it.
---

# Changelog

What's new (`/changelog`) tells people what changed in Monica, day by day. Every entry is one Markdown file in `changelog/`, read straight from the folder: publishing an entry needs no migration and no command. Comments, votes and seen marks are attached to the entry's `id`, never to its file name.

## When a change needs an entry

Write one for:

- a new feature or capability;
- a significant improvement to an existing feature;
- a change to an existing workflow or behaviour;
- a meaningful improvement to the interface or the experience;
- a bug fix that people could notice;
- a performance improvement people can feel.

Write none for:

- a typo or spelling correction;
- a minor copy change;
- a formatting or styling adjustment with no meaningful effect;
- internal refactoring nobody can see;
- a change to tests only;
- a dependency update with no visible effect;
- a change to CI, deployment or development tooling;
- any other trivial maintenance.

When you are unsure, leave it out. An entry nobody needs is noise in front of the entries that matter.

Write one entry per meaningful change, not one per commit and not one per pull request. A pull request carrying two features and a fix carries three entries. Never generate entries from commit messages.

## How to write one

1. Generate a new ULID: `php artisan tinker --execute 'echo Str::ulid();'`. Check with `grep -r "<the ulid>" changelog/` that it has never been used.
2. Create `changelog/<date>-<ulid>-<short-slug>.md`, the date being the day of `published_at` in UTC: `changelog/2026-11-17-01KB1J7XY8M4NPQ2RST6VW9ABC-birthday-age.md`. The application reads nothing from the file name, but the date keeps the folder in the order things shipped.
3. Write the front matter and the description:

```markdown
---
id: 01KB1J7XY8M4NPQ2RST6VW9ABC
title: Birthdays show the age somebody turns
type: feature
area: Contacts
published_at: 2026-11-17T10:00:00Z
link: /vaults
---
Next to a birthday on a contact page, Monica now writes how old the person will be,
and the reminder email says it too.
```

| Key | Rule |
| --- | --- |
| `id` | Required. The ULID from step 1. Never change it once the entry is merged. |
| `title` | Required. One line, in English, saying what changed for the reader. |
| `type` | Required. `feature`, `improvement` or `fix`. |
| `published_at` | Required. The current time in UTC, ending in `Z`: `date -u +%Y-%m-%dT%H:%M:%SZ`. |
| `area` | Optional. The part of Monica it touches, as the reader names it: `Contacts`, `Vault`, `Reminders`, `Account settings`. |
| `link` | Optional. A path inside the application, starting with a single `/`. Never a full URL. |
| body | Required. Markdown, in English. |

Every front matter value fits on one line. Only the body may span several.

4. Run `php artisan monica:localize`, then translate what it reports as missing in every locale, following the [translations skill](../translations/SKILL.md). The title, the area and the body are translation keys like any other string, so the subagents doing the translation are Sonnet ones, as always. `php artisan monica:localize --check` fails until every locale has them.
5. Run `php artisan test --compact tests/Unit/Helpers/ChangelogEntriesTest.php`. It refuses a missing field, an invalid or duplicate ULID, an unknown type, a malformed date and a link that leaves the application.
6. Commit the entry and its translations in the same pull request as the change it describes.

## How to word it

- Say what changed for the reader and why it matters to them. Leave out how it was built: no class names, no table names, no mention of a refactoring.
- Keep the title short and concrete: "Search finds nicknames and companies", not "Improved search".
- A fix says what went wrong in the reader's words: "Reminders for 29 February arrived on 1 March. They now arrive on 28 February."
- Write the plain, calm English the rest of the product uses. The [translations skill](../translations/SKILL.md) and `.claude/rules/translations.md` say how each language then writes it.

## What never changes

- The `id` of an entry already in `main`. Comments and votes hang on it.
- An entry is never deleted to hide it. Correct its text instead; editing the English text drops its translations, and `monica:localize` asks for them again.
