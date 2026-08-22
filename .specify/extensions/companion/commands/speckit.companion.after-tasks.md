---
description: "Capture tasks completion (currentStep=tasks, status=ready-to-implement) into .spec-context.json for the Companion GUI"
---

# Capture Tasks Context

Record the active feature's tasks completion into `.spec-context.json` so the
SpecKit Companion GUI re-renders with the correct step and status. This command
runs as the `after_tasks` lifecycle hook — **state-writing only**; the tasks
document is created by the core `/speckit.tasks` workflow.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip the capture:
  `[companion] Warning: python3 not detected; skipped .spec-context.json capture`.
  Do not fail the host command.

## Execution

Run the writer script from the repository root:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --step tasks --status ready-to-implement --kind complete --by extension
```

`--kind complete` records the tasks step's **completion boundary** (the tasks body already recorded the step's start when it began), so the extension stamps both ends of the span in order. The completion append is idempotent — a re-fired hook never doubles it.

The script resolves the active feature directory on its own, in this order:
`--feature-dir` → `SPECIFY_FEATURE_DIRECTORY` env → `SPECIFY_FEATURE` env →
`.specify/feature.json` → current git branch prefix.

If you already know the feature directory (e.g. the one `/speckit.tasks` just
wrote into), pass it explicitly so resolution is unambiguous:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir specs/<NNN>-<slug> --step tasks --status ready-to-implement --kind complete --by extension
```

## Graceful Degradation

The script is best-effort and never fails the host command:
- If `python3` is missing, skip with the warning above.
- If the active feature directory cannot be resolved, the script prints a warning
  to stderr and exits 0 without writing.

## Output

On success the script prints the path it updated and the values written, e.g.:
`[companion] Updated specs/<NNN>-<slug>/.spec-context.json (currentStep=tasks, status=ready-to-implement, kind=complete, by=extension)`.
The write is atomic (temp file + rename) and appends to the canonical `history[]` without
rewriting existing entries.
