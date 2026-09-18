---
description: "Check the shape of living specs and a feature spec's deltas — a requirement with no scenario, a scenario missing WHEN or THEN, a duplicate heading, a delta pointing at nothing (opt-in, read-only, never halts)"
---

# Spec Shape

Check that every living spec, and every active feature spec's delta sections, are shaped the way every reader of them assumes. A living spec is only worth keeping if what gets folded into it is trustworthy, and until something checked, a requirement with no scenario or a delta pointing at a heading that does not exist landed silently and was found weeks later by a person reading the file.

**Read-only** — it never edits anything, and it **never fails** (always exits success). The fold consumes the same checks and refuses to write on an error; this command only reports. A surrounding workflow may treat findings as a gate; the command itself does not.

This is **opt-in**. With living specs disabled (or no config), it reports nothing and exits clean.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip:
  `[companion] Warning: python3 not detected; skipped the spec shape check`.

## Execution

Run the checker from the repository root. When the invocation names a capability, add `--capability <name>` so only that capability's spec is checked:

```bash
python3 .specify/extensions/companion/scripts/living_validate.py
```

Add `--json` when a caller needs the machine-readable object rather than the list. Each finding carries a severity, a stable code, the path, the line, a sentence saying what is wrong, and a one-line fix.

## What it reports

| Code | Severity | Raised when |
|---|---|---|
| `requirement-without-scenario` | warning | A requirement states a rule and never says how anyone would know it held. |
| `scenario-missing-half` | error | A scenario has a condition and no outcome, or an outcome and no condition. The keywords are recognised with or without emphasis, so `- WHEN …` counts exactly as `- **WHEN** …` does. |
| `duplicate-requirement` | error | Two requirements in one capability share a heading, which is the join key fold-back and coverage use. |
| `unknown-capability` | error | A delta block is marked for a capability the registry does not list. |
| `delta-heading-not-found` | warning | A MODIFIED or REMOVED entry names a heading the target spec does not carry. The fold promotes an unmatched modification into an addition, so this is a defined outcome rather than damage. |
| `unmatched-touches-glob` | warning | A file marker names a pattern matching nothing on disk. |
- `requirements-outside-capability` — every file marker names code the capability does not claim, so nothing resolves for that behaviour.
- `capability-claims-undescribed-code` — the capability claims code no requirement describes, so a change there is briefed with nothing.
| `unbalanced-fence` | warning | A code fence is opened and never closed, so everything after it is invisible to every reader. |

Severity answers exactly one question: whether the fold stops. An error means the durable record would be damaged; a warning means it would be untidy.

## Report

Relay the checker's own output. Where it reports findings, say how many are errors and how many are warnings, and name the file and line for each. Where it reports skipped files, list them with their reasons verbatim — a clean report must never be readable as a verdict on files that were never examined.

Do **not** edit any spec to satisfy a finding as part of this command. Fixing is the author's decision, made with the finding in front of them.
