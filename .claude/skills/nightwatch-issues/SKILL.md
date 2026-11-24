---
name: nightwatch-issues
description: How to work on an issue that Laravel Nightwatch reported, from reading it to closing it. Use when the user names a Nightwatch issue, pastes a nightwatch.laravel.com link, or asks to fix an exception, a slow route, a slow job or a slow command that Nightwatch detected.
---

# Nightwatch issues

Nightwatch reports what goes wrong in production: exceptions, slow routes, slow jobs, slow commands
and slow scheduled tasks. The `nightwatch` MCP server reads and updates its issues. This skill is
about working on one of them. Configuring what Nightwatch collects is the `configure-nightwatch`
skill.

An issue goes through three steps, and each step leaves a mark on the issue so that anybody opening
it in Nightwatch knows where it stands.

## 1. Read the issue

- Read it with `get_issue`, passing the full URL when the user gave one. A ref alone (`#123`)
  needs the application UUID: find it with `list_applications`, never pass the name as the id.
- The issue carries the stack trace, the code context, the occurrences and the environments it
  happened in. Read all of it before you change any code.
- Production data in the issue (an email, a name, a request body) stays in the conversation. Never
  copy it into a test, a commit, a pull request or a comment. Use data from The Office instead, as
  `.claude/rules/testing.md` says.

## 2. Fix it, and say which pull request fixes it

Fix it on a new branch like any other change: a test that reproduces the failure first, then the
fix, then the `definition-of-done` skill.

Once the fix is pushed and its pull request is open, comment on the issue with `add_issue_comment`.
The comment names the pull request and nothing else:

```markdown
Fixed by https://github.com/monicahq/monica/pull/555
```

- Comment only once the pull request exists. A comment announcing a fix that nobody can read is
  worse than none.
- Do not change the status yet. The issue stays open until the fix is merged.
- Put the link to the Nightwatch issue in the pull request description, as a bullet.
- Never mention Claude Code in the comment.

One pull request may fix several issues. Comment on each of them.

## 3. Close it once the pull request is merged

Check the pull request before you touch the issue:

```bash
gh pr view <number> --json state,mergedAt
```

- **Merged**: close the issue with `update_issue`, `status: resolved`, and a comment naming the
  merged pull request: `Resolved by https://github.com/monicahq/monica/pull/555, merged.`
- **Still open**: leave the issue open. Close it later, when you learn the pull request was merged.
- **Closed without being merged**: leave the issue open, and comment that this pull request no
  longer fixes it.

Nightwatch reopens a resolved issue when it happens again. When the fix is merged but not yet
deployed, that reopening is expected and says nothing about the fix. When it reopens after the
deployment, the fix did not hold: read the new occurrences and start again from step 1.

## What not to do

- Never resolve an issue because a fix is written or pushed. Only a merged pull request resolves it.
- Never mark an issue `ignored` unless the user asks.
- Never comment for the sake of commenting: every comment notifies everybody subscribed to the
  issue. The two comments above are the ones this workflow writes.
