---
name: speckit-companion-before-converge
description: Capture converge start (currentStep=converge, status unchanged) into .spec-context.json for the Companion GUI
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.before-converge.md
---

# Capture Converge Start

Record the start of the active feature's converge run into `.spec-context.json` so the SpecKit Companion GUI shows converge running. This command runs as the `before_converge` lifecycle hook and only writes state; the convergence check itself is the core `speckit.converge` workflow.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip the capture: `[companion] Warning: python3 not detected; skipped .spec-context.json capture`. Do not fail the host command.

## Execution

Run the writer script from the repository root:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --step converge --kind start --by extension
```

There is no `--status`: converge owns no status and never moves the spec's status. The start is idempotent, so a re-fired hook never records a second one.

The script resolves the active feature directory on its own, in this order: `--feature-dir`, `SPECIFY_FEATURE_DIRECTORY` env, `SPECIFY_FEATURE` env, `.specify/feature.json`, current git branch prefix.

If you already know the feature directory, pass it explicitly so resolution is unambiguous:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir specs/<NNN>-<slug> --step converge --kind start --by extension
```

## Graceful Degradation

The script is best-effort and never fails the host command:
- If `python3` is missing, skip with the warning above.
- If the active feature directory cannot be resolved, the script prints a warning to stderr and exits 0 without writing.
- If the spec is already `completed` or `archived`, nothing is written.

## Output

On success the script prints the path it updated and the values written, e.g. `[companion] Updated specs/<NNN>-<slug>/.spec-context.json (currentStep=converge, status=unchanged, kind=start, by=extension)`. The write is atomic and appends to `history[]` without rewriting existing entries.