---
name: pull-requests
description: Conventions for writing pull requests, including Conventional Commits titles and structured descriptions. Use when the user wants to create or open a pull request, or write a PR title or description. Trigger whenever a pull request, PR, or merge request is mentioned.
---

# Create a pull request

- The pull request title MUST follow the Conventional Commits naming convention, e.g. `feat: add new locale fr_FR`.
- You SHOULD avoid uppercase in the title, except for proper nouns and acronyms.
- You MUST indicate the issue the PR closes, if that's the case, and reference the issue number. Do not mention anything otherwise.
- You MUST NOT mention Claude Code anywhere.
- The pull request description MUST be as small as possible:
    - use bullet points format
    - describe the changes made in this PR, using as fewer words as possible
    - keep it understandable by non technical people
    - when a new concept is introduced in the codebase, add a few details to let readers understand what it's about
