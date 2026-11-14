---
name: speckit-companion-classify
description: Classify the change size (simple | normal | oversized) so the Companion workflow can right-size the pipeline
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.classify.md
---

# Classify Change Size

Emit a single complexity signal — `simple`, `normal`, or `oversized` — that the Companion
workflow's routing step reads to right-size the pipeline. On the workflow path there is no
`complexityFastPath` on/off setting: the thresholds live here, in the workflow, not in a
VS Code toggle.

This is a **thin, read-only** step. It does not write `.spec-context.json` and does not create or
edit any spec files; it only reports a size.

## Heuristic (thresholds live here, not in a setting)

Estimate the scope of the change from `spec.md` (and `plan.md`/`tasks.md` if they already exist):

<!-- speckit-companion:part sizing -->
- **small** when all three hold:
  1. **Understood**: the outcome is stated, you know what and where to change, and no product or design decision is open.
  2. **Contained risk**: it touches none of these, where a mistake is hard to see or hard to undo: changing or deleting existing stored data or a stored format (a migration; saving new records is fine); security (auth, permissions, secrets); a contract others already consume (a public API, CLI flag, config format or export, changed or removed; adding one is fine); concurrency (locks, races, ordering); delivery (installers, release, CI, dependencies); or behavior no test would catch if it broke.
  3. **Resumable from the diff**: if the session stopped now, someone could finish from the request and `git diff` alone.

  A change of up to about 5 files and 10 tasks that touches none of the items in 2 is small: several files of one understood change do not make it normal. Counts alone never make a change large.
- **oversized**: the change clearly spans several subsystems, or is roughly double the size one session handles comfortably (more than about 10 files or 20 tasks).
- **normal**: anything in between (the default).
<!-- /speckit-companion:part sizing -->
When unsure, prefer `normal` — the routing step's safe default is the full pipeline, so an
ambiguous estimate never skips a phase.

## Output

Print exactly one line so the size is visible in the run log:

```text
[companion] size=<simple|normal|oversized>
```

The Companion workflow on the engine routes on the size specify recorded, read back
by a shell step, not on this command's output. Routing contract:

<!-- speckit-companion:part routing -->
- `simple` → the workflow folds toward implement (less ceremony). `simple` is the verdict
  every reader of the recorded size expects; `small` names the *bar*, never the verdict.
- `oversized` → the workflow prints a visible warning and still runs the **full** pipeline. It
  never silently skips a phase.
- `normal` (and any unresolved value) → the full pipeline.
<!-- /speckit-companion:part routing -->