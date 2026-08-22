---
description: "Report living-spec drift — per capability, the source files changed since the spec was last committed (opt-in, never halts)"
---

# Spec Drift

Show, for each living-spec capability, the source files that changed since that capability's spec was last committed — and **how** each one slipped. A living spec only stays honest if changes to its area flow back into it; this command surfaces the ones that didn't. **Read-only** — it never edits anything, and it **never fails** (always exits success). A surrounding workflow or CI may treat findings as a gate; the command itself does not.

This is **opt-in**. With living specs disabled (or no config), it reports nothing and exits clean.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip:
  `[companion] Warning: python3 not detected; skipped drift`.
  Do not fail the host command.

## Execution

Run the drift detector from the repository root:

```bash
python3 .specify/extensions/companion/scripts/drift.py
```

The script reads the capability registry (`living-specs.yml`), reuses the
resolver for capability membership, and uses git to find what changed since each
capability's `capabilities/<name>/spec.md` was last committed. Each drifted file
is classified:

- **`tracked`** — the file went through the Companion pipeline (it appears in a
  `specs/*/.spec-context.json` changed set) but was never folded back into the
  living spec → a missed sync.
- **`unspeced`** — the file changed entirely outside the pipeline; the living spec
  never saw it. The more concerning of the two.

Files matching any glob in the registry's `exempt` list (default `*.config.*`, `*.test.*`, `**/migrations/**`) are filtered out. A capability whose spec is not yet committed is skipped with an informational note, and the run ends on a counts line — e.g. `0 checked, 2 skipped (spec.md not yet committed)` — so a check that examined nothing never reads as clean. The `✓ All N checked capabilities in sync.` line is reserved for a run that checked at least one capability and found it clean.

Add `--working` (opt-in) to also see the **working tree**: uncommitted edits, deletions, and untracked files then count as drift alongside the committed history. The contract is unchanged — same checked/skipped counts semantics, same never-fails exit `0` — and without the flag the report reads committed history only, exactly as before:

```bash
python3 .specify/extensions/companion/scripts/drift.py --working
```

Add `--json` for a machine-readable object (used by tooling/CI). It carries a `checked` count alongside the `capabilities` and `skipped` lists, so a caller can tell "clean" from "did not run" — the exit code stays `0` either way — plus a `working` boolean naming which mode produced it:

```bash
python3 .specify/extensions/companion/scripts/drift.py --json
```

## What to do with the report

Drift is a signal, not an error. To fold the reported changes back in one pass, run `/speckit.companion.living-sync` — the write-side twin of this report (it consumes the same `--working` computation). Otherwise, for each `unspeced` or `tracked` row, either fold the change into the living spec by hand (e.g. run `/speckit.companion.living-adopt` for the area, or write a delta spec) or add the path to the registry's `exempt` list if it shouldn't be tracked. The command never blocks the pipeline on its own.
