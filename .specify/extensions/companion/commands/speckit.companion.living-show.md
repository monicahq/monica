---
description: "Print one slice of a living spec — a capability's requirement headings, one requirement in full, or the requirements that describe one file (opt-in, read-only, never halts)"
---

# Show

Print part of a living spec instead of opening it. A capability's spec runs to hundreds of lines, and almost every question about one is about a single requirement: what does it say, what are its scenarios, which rules describe this file I am editing. This is the terminal twin of the load steps' selective read — same parser, same counts, no viewer required.

**Read-only** — it never edits anything, and it **never fails** (always exits success). Every unanswerable question is an answer: an unregistered capability, a missing spec file, a name that matches nothing, a file nothing claims.

This is **opt-in**. With living specs disabled (or no config), it reports nothing and exits clean.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip:
  `[companion] Warning: python3 not detected; skipped the living spec slice`.

## Execution

Run one of these from the repository root, whichever the request asks for:

```bash
# every requirement heading in one capability
python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --headings <capability>

# one requirement in full, including its scenarios
python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --requirement "<heading>"

# the requirements that describe one source file
python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --file <path>
```

Add `--capability <name>` to `--requirement` to search one capability instead of all of them. Add `--json` when a caller needs the object rather than the list.

## What each mode answers

| Mode | Answers |
|---|---|
| `--headings` | What rules does this capability state? One heading per line, in file order, with the count. |
| `--requirement` | What does this one rule say, and how would anyone know it held? The heading, the normative prose, and the scenarios. |
| `--file` | Which durable rules describe this file? Grouped by capability, most-specific capability first. |

A requirement carrying no file marker is returned by `--file` for every file its capability claims — a marker can only narrow, never starve.

## Report

Relay the script's own output. Do not summarize a requirement, paraphrase a scenario, or truncate a body: the point of the command is that a reader sees the spec's own words. Where the script reports that a capability is unregistered, has no spec file on disk, or that a name matched nothing, say that in the same terms and print the alternatives it listed.

Do **not** edit any spec as part of this command.
