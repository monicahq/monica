---
name: pull-requests
description: Conventions for writing pull requests, including Conventional Commits titles and structured descriptions. Use when the user wants to create or open a pull request, or write a PR title or description. Trigger whenever a pull request, PR, or merge request is mentioned.
---

# Create a pull request

- The pull request title MUST follow the Conventional Commits naming convention, e.g. `feat: add new locale fr_FR`.
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

## Screenshots for a user interface change

- A pull request that changes anything a person sees MUST carry a screenshot. That covers a new screen, a changed layout, spacing, a component, wording drawn on a page, a state (empty, error, loading), and anything else visible in a browser.
- You MUST take them with the [take-screenshot skill](../take-screenshot/SKILL.md), which uses Iris. The application is served at `http://monica.test`.
- A screen that already existed MUST show a before and an after, in that order, so a reviewer sees what changed rather than only the result. A screen that did not exist has no before, and carries the after alone.
- Both shots MUST come from the same URL, at the same size, with the same data. The change is the only thing that may differ between them.

### Capturing the before

1. `git switch main`.
2. Rebuild the front end with `bun run build`, because the styles built from your branch do not match main and would draw the old page wrongly.
3. Capture `.github/screenshots/<feature>-before.png`. The file is untracked, so it survives the switch back.
4. `git switch -` to return to your branch, then `bun run build` again.
5. Capture `.github/screenshots/<feature>-after.png`.

### Putting them in the pull request

- The screenshots are committed to `.github/screenshots/`, in kebab-case, named after the feature. This is the one place screenshots belong in Git.
- Commit and push them before writing the description, then read the commit with `git rev-parse HEAD`.
- Link each image with that full commit SHA, never with a branch name, so it keeps working after the branch is deleted:

```text
![Before: the field type picker as a plain list](https://github.com/monicahq/erica/blob/<sha>/.github/screenshots/field-type-picker-before.png?raw=1)
![After: the field type picker showing a preview of the chosen type](https://github.com/monicahq/erica/blob/<sha>/.github/screenshots/field-type-picker-after.png?raw=1)
```

- The alt text MUST start with `Before:` or `After:` and then say what the image shows. That label is what a reviewer reads, and it keeps working when the image does not load.
- An image is a line of its own, which the description rules above already allow. Do not introduce it with a sentence.
