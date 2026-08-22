---
description: "Capture the current spec-kit step into .spec-context.json for the Companion GUI"
---

# Capture Spec Context

Record the active feature's current pipeline step into `.spec-context.json` so the
SpecKit Companion GUI re-renders with the correct step and status. This command
runs as the `after_specify` lifecycle hook — **state-writing only**; the spec
directory and files are created by the core `/speckit.specify` workflow.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip the capture:
  `[companion] Warning: python3 not detected; skipped .spec-context.json capture`.
  Do not fail the host command.

## Execution

Run the writer script from the repository root:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --step specify --status specified --by extension
```

The script resolves the active feature directory on its own, in this order:
`--feature-dir` → `SPECIFY_FEATURE_DIRECTORY` env → `SPECIFY_FEATURE` env →
`.specify/feature.json` → current git branch prefix.

If you already know the feature directory (e.g. the one `/speckit.specify` just
created), pass it explicitly so resolution is unambiguous:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir specs/<NNN>-<slug> --step specify --status specified --by extension
```

## Graceful Degradation

The script is best-effort and never fails the host command:
- If `python3` is missing, skip with the warning above.
- If the active feature directory cannot be resolved, the script prints a warning
  to stderr and exits 0 without writing.

## Output

On success the script prints the path it updated and the values written, e.g.:
`[companion] Updated specs/<NNN>-<slug>/.spec-context.json (currentStep=specify, status=specified, by=extension)`.
The write is atomic (temp file + rename) and appends to the canonical `history[]` without
rewriting existing entries.
