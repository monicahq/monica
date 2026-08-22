---
description: "Report where the active spec stands — current step, status, recorded decisions, and the next action — from .spec-context.json"
---

# Spec Status

Summarize the active feature's position in the spec-driven pipeline so you can see, at a glance, the current step, its status, the decisions recorded so far, and what to do next. **Read-only** — this command never writes `.spec-context.json`.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip:
  `[companion] Warning: python3 not detected; skipped status`.
  Do not fail the host command.

## Execution

Run the resolver from the repository root:

```bash
python3 .specify/extensions/companion/scripts/status-context.py
```

The script resolves the active feature directory on its own, in this order:
`--feature-dir` → `SPECIFY_FEATURE_DIRECTORY` env → `SPECIFY_FEATURE` env →
`.specify/feature.json` → current git branch prefix.

Pass the directory explicitly when you already know it:

```bash
python3 .specify/extensions/companion/scripts/status-context.py --feature-dir specs/<NNN>-<slug>
```

The script reads `.spec-context.json`. When that file is missing or malformed, it
falls back to deriving state from the on-disk artifacts (`spec.md` → specify,
`plan.md` → plan, `tasks.md` → tasks/implement) and marks the output
`source: derived`.

## Output

Print the human summary block the script emits, e.g.:

```text
Spec: <name>   (source: state|derived)
Step: <currentStep>   Status: <status>
Decisions:
  - <decision>
Next: <action>  →  <command|—>
```

- No decisions recorded → `Decisions: (none recorded)`.
- Spec fully implemented / completed / archived → `Next: Pipeline complete  →  —`.
- Inside the implement step → `Next: Continue implementation at <task>  →  dispatching /speckit.implement`.
- No state and no spec files → `Nothing to summarize (no spec files or recorded state found).`

The script also emits a final `RESOLUTION: { … }` JSON line. It is for
`/speckit.companion.resume` and tests — you do not need to surface it to the user.

## Graceful Degradation

Best-effort and never fails the host command: if `python3` is missing or the
feature directory cannot be resolved, it warns to stderr and exits 0.
