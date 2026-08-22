---
description: "Continue the spec-driven pipeline from the last completed step, carrying recorded decisions into scope, and dispatch the next command"
---

# Resume Spec

Pick the pipeline back up where it stopped. This command reads the active
feature's recorded state, resolves the next step — carrying the recorded
`decisions` into scope — and dispatches the next `/speckit.*` command. Inside the
implement step it continues at the next unchecked task.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip:
  `[companion] Warning: python3 not detected; skipped resume`.
  Do not fail the host command.

## Execution

1. Resolve the next action from the repository root:

   ```bash
   python3 .specify/extensions/companion/scripts/status-context.py
   ```

   (Pass `--feature-dir specs/<NNN>-<slug>` when you already know it.) The script
   reads `.spec-context.json`, or derives state from on-disk files when it is
   missing/malformed.

2. Parse the final `RESOLUTION: { … }` JSON line the script prints. It contains
   `currentStep`, `status`, `decisions[]`, `nextStep`, `nextCommand`,
   `nextActionLabel`, `nextTask`, and `complete`.

3. Branch on the resolution:

   - **`complete: true`** → print `Pipeline complete — nothing to resume.` and stop.
     Do not dispatch anything.
   - **`empty: true`** (no recorded state and no spec files) → print
     `Nothing to resume (no spec files or recorded state found).` and stop.
   - **`nextTask` is set** (inside the implement step) → continue implementation at
     `nextTask`: invoke `/speckit.implement`, instructing it to resume at the next
     unchecked task.
   - **otherwise** → invoke `nextCommand` (e.g. `/speckit.plan`, `/speckit.tasks`,
     `/speckit.implement`).

4. When you dispatch, state the recorded `decisions[]` as in-scope context for the
   step you are running, so prior decisions carry forward and the user does not
   re-specify them.

## Dispatch note

Resume dispatches the **already-installed** `/speckit.*` commands. It does not
require a `specify workflow resume` CLI subcommand, so it works on the stock
installed spec-kit version. The dispatched command runs its own `after_*` capture
hook, which writes the resulting `history[]` entry — resume itself writes no state.

## Output

```text
Resuming <name> from <currentStep> (<status>).
Decisions in scope:
  - <decision>
Next: <action>  →  dispatching <command>
```

- Tasks/implement step → `Next: Continue implementation at <task>  →  dispatching /speckit.implement`.
- No decisions recorded → omit the "Decisions in scope" block.

## Graceful Degradation

Best-effort: if `python3` is missing or the feature directory cannot be resolved,
warn to stderr and stop without dispatching. Never fail the host command.
