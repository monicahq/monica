---
description: "Mark the active spec completed — the Companion workflow's terminal step (writes status: completed)"
---

# Mark Spec Complete

Promote the active feature to the terminal `completed` status in `.spec-context.json`. This is the
Companion workflow's final step: it runs after `implement` has finished so the spec lands at the
end of the Active → Completed lifecycle. The **command** writes `completed` via the shared
`write-context.py` path — you never hand-edit `.spec-context.json` to do it.

The stock `speckit` workflow has no terminal step and force-closes without ever writing `completed`;
this step is what gives the Companion pipeline its explicit completion gate.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip:
  `[companion] Warning: python3 not detected; skipped mark-complete`.
  Do not fail the host command.

## Execution

Run the writer from the repository root:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --mark-complete --by ai
```

The script resolves the active feature directory on its own (`--feature-dir` →
`SPECIFY_FEATURE_DIRECTORY` → `SPECIFY_FEATURE` → `.specify/feature.json` → git branch prefix).
Pass `--feature-dir specs/<NNN>-<slug>` when you already know it.

`--mark-complete` keeps `currentStep` at `implement` (the last real step) and sets `status:
completed`, preserving the canonical invariant that the last `history` entry's step equals
`currentStep`. It is the only sanctioned writer of `completed`.

### Fold living-spec deltas (opt-in, best-effort)

**Account for every loaded capability first — a delta or an explicit skip, never silence.** Living
specs stay current only if completion writes the change back, so before folding, read
`livingSpecs.loaded` in this feature's `.spec-context.json` and go through **every** name in it. For a
loaded capability whose *behavior* this feature actually changed, append a delta block to this
feature's `spec.md` — `## ADDED / MODIFIED / REMOVED / RENAMED Requirements` — capturing the real new
or changed requirement, and mark each block with `<!-- capability: <name> -->` so the fold routes it
to the right spec. For a loaded capability this feature did **not** change (one you merely read for
context), record an explicit skip instead — one call per untouched capability, so "correctly nothing"
stays distinguishable from "silently nothing":

```bash
python3 .specify/extensions/companion/scripts/write-context.py --living-spec-skip "<name>: <one-line reason it wasn't changed>"
```

Never invent requirements to pad the list. By the end, every name in `livingSpecs.loaded` is
accounted for — a delta block or a recorded skip; a capability that is neither is a hole the fold
flags loudly. The writes land in the feature's PR diff, so they are reviewed there.

After the completion write succeeds, fold the deltas you just authored into the durable living
spec(s) — OpenSpec's "archive" step. Run from the repository root:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --fold-living-spec --by ai
```

This parses the feature spec for `## ADDED / MODIFIED / REMOVED / RENAMED Requirements` blocks and
applies each to the resolved `capabilities/<name>/spec.md` — the changed-files-matched capability for
unmarked blocks, and every `<!-- capability: <name> -->`-marked capability for the rest, so each
capability spec receives only its own requirements. It is **opt-in** (only acts when
`living-specs.yml` sets `enabled: true`), a **clean no-op** when the spec carries
no delta block, **idempotent** on re-run, and records the synced
capability names onto `livingSpecs.synced` in `.spec-context.json`. Best-effort — it never fails the
host command.

## Graceful Degradation

Best-effort and idempotent:
- If `python3` is missing, skip with the warning above (never fail the host command).
- A spec already at `completed` or `archived` is left untouched — the script reports it and exits 0.

## Output

On success:
`[companion] Marked specs/<NNN>-<slug>/.spec-context.json complete (status=completed, by=ai)`.
