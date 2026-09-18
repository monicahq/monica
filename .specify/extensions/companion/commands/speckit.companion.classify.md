---
description: "Classify the change size (simple | normal | oversized) so the Companion workflow can right-size the pipeline"
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
- **small**: the change plausibly touches **≤ 5 files** and decomposes into **≤ 10 tasks**.
- **oversized**: the change clearly exceeds the small bar by a wide margin (broad multi-subsystem
  work, many new files, or a long task list).
- **normal**: anything in between (the default).
<!-- /speckit-companion:part sizing -->
When unsure, prefer `normal` — the routing step's safe default is the full pipeline, so an
ambiguous estimate never skips a phase.

## Output

Print exactly one line so the size is visible in the run log:

```text
[companion] size=<simple|normal|oversized>
```

Expose the same value as structured output `size` (so a `switch` node can read
`steps.classify.output.size`). Routing contract:

<!-- speckit-companion:part routing -->
- `simple` → the workflow folds toward implement (less ceremony). `simple` is the verdict
  every reader of the recorded size expects; `small` names the *bar*, never the verdict.
- `oversized` → the workflow prints a visible warning and still runs the **full** pipeline. It
  never silently skips a phase.
- `normal` (and any unresolved value) → the full pipeline.
<!-- /speckit-companion:part routing -->
