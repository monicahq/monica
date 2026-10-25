---
name: pull-requests
description: Conventions for writing pull requests, including Conventional Commits titles and structured descriptions. Use when the user wants to create or open a pull request, or write a PR title or description. Trigger whenever a pull request, PR, or merge request is mentioned.
---

# Create a pull request

- The pull request title MUST follow the Conventional Commits naming convention, e.g. `feat(scope): add new locale fr_FR`.
- You SHOULD avoid uppercase in the title, except for proper nouns and acronyms.
- You MUST indicate the issue the PR closes, if that's the case, and reference the issue number. Do not mention anything otherwise.
- You MUST NOT mention Claude Code anywhere.
- The pull request description MUST be bullet points and nothing else:
    - every line MUST be a bullet, a short heading grouping bullets, or an image
    - you MUST NOT write a paragraph anywhere, including the opening line and the closing line. No sentence introducing the change, no sentence summarising it, no narration between bullets. A description whose first line is prose is wrong however good the prose is
    - a heading MUST be two or three words and carry no prose of its own
    - one idea per bullet, one line each wherever it fits
    - describe the changes made in this PR, using as fewer words as possible
    - do not restate the title, and do not list what the diff already shows
    - keep it understandable by non technical people
    - when a new concept is introduced in the codebase, add a few details to let readers understand what it's about, still as a bullet

## Scope

- You MUST add one scope to every commit message and every PR title, chosen from the list below: `type(scope): sentence`.
- Choose the scope that names the area of the application the change is about. When a change touches several areas, choose the one the reader of the commit would look for.
- Do NOT invent a scope. When none of the scopes fits, ask before you add one to this list.
- Write the scope in lowercase, exactly as listed.

| Scope | Area |
| --- | --- |
| `reminders` | Reminders, dates, occurrences, the reminder detail page |
| `notifications` | Notification channels, notification settings, the in-app notifications, quiet hours and the daily summary |
| `contacts` | Contact record, contact list, timeline, duplicates, relationships, favourites |
| `templates` | Templates, pages, sections, fields, entity types, value lists |
| `vaults` | Vaults, vault dashboard, vault settings, vault navigation |
| `i18n` | Locales, translations, pluralization, number and date formats, time zone |
| `api` | The JSON API, the OpenAPI document, API keys, CardDAV and CalDAV |
| `mcp` | The MCP server, its OAuth flow and its tools |
| `auth` | Sign in, passkeys, passwords, two factor authentication, magic link, security emails |
| `ui` | Design system, layout, header, buttons, Tailwind, empty states, print stylesheet |
| `docs` | Documentation portal, product guide, constitution, specifications, project rules |
| `billing` | Stripe, paywall, subscription and purchase, storage quota |
| `admin` | The administrator space and the configuration of the instance |
| `marketplace` | The marketplace, and the sharing of a template or a section |

A change with no area of the application, such as a dependency update or a build or CI change, takes the scope `deps`, `build` or `ci`.

## Screenshots for a user interface change

- A pull request that changes anything a person sees MUST carry a screenshot. That covers a new screen, a changed layout, spacing, a component, wording drawn on a page, a state (empty, error, loading), and anything else visible in a browser.
- The API reference portal at `/docs/api` is the one exception, and a change to it MUST NOT carry a screenshot. Its pages are generated from the definition files in `resources/docs/api`, so a shot of one shows the portal rendering exactly as it always renders, and the endpoints and their wording are what a reviewer has to read. Describe them in a bullet instead. A change to the components or the layout the portal is drawn with is a user interface change like any other and still needs one.
- You MUST take them with the [take-screenshot skill](../take-screenshot/SKILL.md), which uses Iris. Capture from the address your local server gives the application, and check that the page answers there before capturing it, since `APP_URL` can name another host.
- A screenshot MUST show the screen as the branch draws it, and nothing else. Never capture the screen as it stood before the change: a reviewer reads the diff for that.
- Capture from your own branch, with the front end built from it (`bun run build`), so the styles match the markup.
- Screenshots are temporary review artifacts and MUST stay outside the repository. Write them to `/tmp/<feature>.png`.

### Putting them in the pull request

- Screenshots MUST NOT be added to Git or stored anywhere in the repository.
- Use GitHub CLI 2.99.0 or newer, which uploads images directly to GitHub with the repeatable `--attach` flag.
- If `gh pr create --help` does not list `--attach`, upgrade GitHub CLI before creating the pull request. Never fall back to committing the screenshots.
- Put the alt text after `#` in the same argument:

```bash
gh pr create \
  --title "feat(templates): improve the field type picker" \
  --body-file /tmp/pr-body.md \
  --attach '/tmp/field-type-picker.png#The field type picker showing a preview of the chosen type'
```

- The alt text MUST say what the image shows. That is what a reviewer reads, and it keeps working when the image does not load.
- Where a change touches several screens or several states, attach one shot per screen or per state, each with its own alt text.
- An image is a line of its own, which the description rules above already allow. Do not introduce it with a sentence.
- After creating the pull request, read it with `gh pr view --json body,url` and verify that every screenshot appears as a GitHub attachment. A pull request is not complete while an attachment is missing.
