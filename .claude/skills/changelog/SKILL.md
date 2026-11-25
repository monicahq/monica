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
title: Upcoming age is now shown next to a birthday
type: feature
area: Contacts
published_at: 2026-11-17T10:00:00Z
link: /vaults
---
Until now, a birthday on a contact page showed only the date, so you had to work out
how old the person was turning. Now Monica shows the age right next to it, and the
reminder email mentions it too.
```

| Key | Rule |
| --- | --- |
| `id` | Required. The ULID from step 1. Never change it once the entry is merged. |
| `title` | Required. One line, in English, naming what the reader now gets. See [How to word it](#how-to-word-it). |
| `type` | Required. `feature`, `improvement` or `fix`. |
| `published_at` | Required. The current time in UTC, ending in `Z`: `date -u +%Y-%m-%dT%H:%M:%SZ`. |
| `area` | Optional. The part of Monica it touches, as the reader names it: `Contacts`, `Vault`, `Reminders`, `Account settings`. |
| `link` | Optional. A path inside the application, starting with a single `/`. Never a full URL. |
| `screenshot_1` to `screenshot_6` | Optional. One line, in English, saying what the screenshot shows. See [Screenshots](#screenshots). |
| body | Required. Markdown, in English: how it was before, then what changes now. |

Every front matter value fits on one line. Only the body may span several.

4. When the change can be seen, add its screenshots as [Screenshots](#screenshots) says.
5. Run `php artisan monica:localize`, then translate what it reports as missing in every locale, following the [translations skill](../translations/SKILL.md). The title, the area, the body and the screenshot descriptions are translation keys like any other string, so the subagents doing the translation are Sonnet ones, as always. `php artisan monica:localize --check` fails until every locale has them.
6. Run `php artisan test --compact tests/Unit/Helpers/ChangelogEntriesTest.php`. It refuses a missing field, an invalid or duplicate ULID, an unknown type, a malformed date, a link that leaves the application, and every screenshot defect listed below.
7. Commit the entry, its screenshots and its translations in the same pull request as the change it describes.

## Screenshots

An entry about something a person can see carries screenshots, wherever the change can be shown: a new screen, a new or moved button, a changed layout, a new state of a form. An entry with nothing to see (a fix in an email, a performance gain, an API change) carries none.

### What to capture

- **One screenshot.** Add a second only when it shows something the first cannot. Six at most.
- **The step that shows the most value.** A change spread over several screens or steps is shown by the one where the reader sees what they gain, never by the confirmation or congratulation screen at the end. Capture the other steps only when the request asks for them.
- **The part that changed.** Frame and zoom on the element or region, with enough around it to recognise where it is. Leave out the top bar, the sidebar and the empty page around it, unless the change is in them.
- **The example account**, in English and the light theme, so every screenshot reads the same way.

### How to take it

Use Iris, as the [take-screenshot skill](../take-screenshot/SKILL.md) says, through a signed link to the screen, with the debug bar off:

```bash
php artisan monica:screenshot-link monica.geller@centralperk.example --to=/vaults/<vault>/contacts/<contact>
iris '<the link>' --selector '<css of the part that changed>' --padding 24 --scale 2 --size 720x900 -o /tmp/<id>-1.png
```

- **Always `--scale 2`.** What's new shows every screenshot as a double density image, at half its pixel size: a capture 1440 pixels wide is drawn 720 pixels wide, and stays sharp on a high density screen. A capture taken at `--scale 1` would be drawn at half the size it should be.
- **A narrow window, about 720 pixels.** What's new keeps the proportions of the image and never crops it. Its column is about 690 pixels wide, so a capture wider than that is shrunk to fit, and a long thin strip ends up unreadable. A narrow window makes the screen wrap its content onto several lines, which gives a compact image that reads at its natural size. Widen `--size` only when the change needs the room, and never beyond 800 pixels.
- **The part that changed, with `--selector`.** When no single element holds the change, drop `--selector` and give `--size` the width and height of the region instead.
- Iris opens a URL and nothing else, so the step you capture has to be reachable by its address.
- The signature of the link covers the scheme. When the local server redirects `http` to `https`, mint the link with `APP_URL` set to the `https` address for that one command, or the screen answers "Invalid signature".

Open the image and look at it before going further. A crop that cuts the change, shows a loading state, or lands on another screen (a sign in, an email confirmation) is taken again.

### How to store and declare it

1. Convert it to WebP, named after the entry's `id` and the screenshot's position, without resizing it:

```bash
magick /tmp/<id>-1.png -quality 82 public/changelog/<id>-1.webp
```

Never resize the capture. Shrinking it breaks the double density, and What's new would draw it smaller than the screen it shows. The file must stay under 1 MB, which a capture of a region a few hundred pixels wide stays well under.

2. Declare it in the front matter, its value saying what it shows:

```markdown
screenshot_1: The age next to a birthday on a contact page
```

Number the screenshots from 1 without a gap. The order of the numbers is the order the reader sees them in.

The test of step 6 refuses a declared screenshot whose file is missing, a file that is not a valid WebP image or is over 1 MB, an empty description, a gap in the numbers, more than six screenshots, and a file in `public/changelog` that no entry declares.

## How to word it

### The title

Start with the thing the reader gets, then say what is now true of it, with "now". The screen or the
product is not the subject.

| Write | Not |
| --- | --- |
| Template name is now shown on a contact page | A contact page names its template |
| Nicknames and companies now show up in search | Search finds nicknames and companies |
| Reminders for 29 February now arrive on 28 February | Improved reminders |

Keep it short and concrete. A vague title ("Improved search") says nothing.

### The body

Two parts, in this order, in the simplest and friendliest words you can find:

1. **Before.** How it was until now, as the reader lived it: "Until now, nothing on a contact page
   told you which template it was built from."
2. **Now.** What this change gives them: "Now its name is shown under the person's name. Select it
   to open the template."

A fix follows the same order: what went wrong, then what happens now.

Talk to the reader as "you", in short sentences, the way you would tell a friend. Leave out how it
was built: no class names, no table names, no mention of a refactoring.

### Tone

Write the plain, friendly English the rest of the product uses. The [translations skill](../translations/SKILL.md) and `.claude/rules/translations.md` say how each language then writes it.

## What never changes

- The `id` of an entry already in `main`. Comments and votes hang on it.
- An entry is never deleted to hide it. Correct its text instead; editing the English text drops its translations, and `monica:localize` asks for them again.
