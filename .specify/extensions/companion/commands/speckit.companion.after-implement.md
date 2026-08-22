---
description: "Capture per-task implement progress (currentStep=implement) into .spec-context.json for the Companion GUI"
---

# Capture Implement Context

Record the active feature's implementation progress into `.spec-context.json` so the
SpecKit Companion GUI re-renders with the correct step and status. This command
runs as the `after_implement` lifecycle hook. It uses the writer's **TASK-SYNC mode**: it reads the completed task markers in the feature's `tasks.md` and appends one transition per completed task (idempotent — already-recorded tasks are skipped), ending at `status=implemented` when every marker is checked, otherwise `status=implementing`.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip the capture:
  `[companion] Warning: python3 not detected; skipped .spec-context.json capture`.
  Do not fail the host command.

## Execution

Run the writer script from the repository root, passing the active feature's `tasks.md` path:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --step implement --status implemented --by extension --tasks-file specs/<NNN>-<slug>/tasks.md
```

Pass the active feature's `tasks.md` path to `--tasks-file`. If feature resolution is unambiguous the script resolves the feature directory on its own (in this order: `--feature-dir` → `SPECIFY_FEATURE_DIRECTORY` env → `SPECIFY_FEATURE` env → `.specify/feature.json` → current git branch prefix), but `--tasks-file` must still point at that feature's `tasks.md`. The `--status implemented` value is the terminal status applied only when all task markers are checked; partial completion records `implementing` automatically.

## Graceful Degradation

The script is best-effort and never fails the host command:
- If `python3` is missing, skip with the warning above.
- If the active feature directory cannot be resolved, the script prints a warning
  to stderr and exits 0 without writing.

## Output

On success the script prints the path it updated and the values written, e.g.:
`[companion] Updated specs/<NNN>-<slug>/.spec-context.json (currentStep=implement, status=implementing, by=extension)`.
The write is atomic (temp file + rename) and appends to the canonical `history[]` without
rewriting existing entries.
