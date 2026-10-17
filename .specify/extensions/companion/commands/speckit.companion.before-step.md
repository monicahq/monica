---
description: "Capture a spec-kit step's start into .spec-context.json for the Companion GUI"
---

# Capture Step Start

Record that the step you are about to run has started, so the SpecKit Companion GUI can time it. This command runs as the `before_specify`, `before_plan`, `before_tasks` and `before_implement` lifecycle hooks and only writes state; the step's work is the core `speckit.*` command that invoked it.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip the capture: `[companion] Warning: python3 not detected; skipped .spec-context.json capture`. Do not fail the host command.

## Execution

Which step is starting decides what to run.

**Plan, tasks or implement.** Run the writer script from the repository root with that step's values:

| Step | `--step` | `--status` |
|---|---|---|
| plan | `plan` | `planning` |
| tasks | `tasks` | `tasking` |
| implement | `implement` | `implementing` |

```bash
python3 .specify/extensions/companion/scripts/write-context.py --step <step> --status <status> --kind start --by extension
```

The start is idempotent: when the GUI already recorded this step's start at dispatch, the call appends nothing and the earlier time stands.

**Specify.** Do not run the writer. The new feature directory does not exist yet, and `.specify/feature.json` still points at the previous spec, so a write now would land on finished work. Read the real clock and keep what it prints:

```bash
date -u +%Y-%m-%dT%H:%M:%SZ
```

On PowerShell: `(Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")`. The `after_specify` hook records it as the step's start once the directory exists. Never type a time yourself.

## Graceful Degradation

The script is best-effort and never fails the host command:
- If `python3` is missing, skip with the warning above.
- If the active feature directory cannot be resolved, the script prints a warning to stderr and exits 0 without writing.
- If the spec is already `completed` or `archived`, nothing is written.

## Output

On success the script prints the path it updated and the values written, e.g. `[companion] Updated specs/<NNN>-<slug>/.spec-context.json (currentStep=plan, status=planning, kind=start, by=extension)`. The write is atomic and appends to `history[]` without rewriting existing entries.
