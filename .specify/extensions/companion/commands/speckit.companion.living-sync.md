---
description: "Sync living specs from your current changes — group working-tree changes (uncommitted included) by capability and update every affected spec in one pass (opt-in, update-not-regenerate, never halts)"
---

# Sync Living Specs

Bring every affected living spec up to date with the code **as it is right now** — uncommitted edits, deletions, and untracked files included — in a single pass. This is the one-command loop for a developer who codes directly, without running the Companion pipeline: no drift report to read, no capability to hand-pick, no blind spot for work that isn't committed yet. The updated spec files are left as ordinary working-tree edits so they commit **together with the code that caused them**.

This is **opt-in**. With living specs disabled (or no config), it reports nothing to do and exits clean. It **never fails** the host run: any miss degrades into a warning and a skip.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and stop here without failing:
  `[companion] Warning: python3 not detected; skipped living-spec sync`.

## Execution

### 1. Compute the sync plan

Run the drift detector in working-tree mode from the repository root:

```bash
python3 .specify/extensions/companion/scripts/drift.py --working --json
```

This is the same engine `/speckit.companion.living-drift` uses — resolver membership, exempt globs, per-capability baselines, skip reasons — with `--working` widening each capability's changed set to the working tree (baseline→worktree diff plus untracked files). Its JSON output **is** the sync plan; do not regroup the files yourself with ad-hoc git commands.

Read the result:

- `enabled: false` → report `Living specs are off in this repo; nothing to sync.` and stop (success).
- `checked: 0` → nothing could be examined; report the `skipped` list with each reason and stop (success).
- Otherwise the plan is `capabilities[]`: each entry with a non-empty `drifted` list is a capability to sync (`name`, its spec at `spec`, and the changed files in `drifted[].file`); entries with `drifted: []` are already in sync and are left untouched.

### 2. Update each affected capability — every one, no hand-picking

For **each** capability with drifted files, edit its spec file (the `spec` path) in place, scoped to **that capability's** `drifted[].file` list:

> The capability has drifted — the code it describes changed since the spec was last committed (working-tree changes included). **UPDATE, do not regenerate**: keep every requirement, clarification, and acceptance scenario already written, and revise only what the changed files require. Read the listed changed files, work out what behavior was added, changed, or removed, and reflect exactly that. A file deleted from the working tree means its behavior was **removed** — reflect the removal rather than describing the file as if it still existed. Never rewrite untouched sections, never reorder requirements, and never flatten hand-written detail into a fresh draft.

Work through the capabilities one at a time. If one capability's update fails (unreadable file, unresolvable content), warn, skip it, and continue with the rest — one bad capability never blocks the others.

### 3. Handle the skipped capabilities honestly

Report every entry in the plan's `skipped` list with its reason, verbatim. In particular:

- `spec.md not yet committed` — the capability has no committed baseline to diff against. Do **not** draft or redraft its spec here; that is bootstrap work, and `/speckit.companion.living-adopt` owns it. Say so.
- Git/shallow-clone reasons — nothing to do but state them.

### 4. Report — and leave the edits uncommitted

End with a short report:

- **Synced** — each updated capability, with how many changed files were folded in.
- **Skipped** — each skipped capability and its reason.
- A closing note that the spec edits are **left uncommitted on purpose** so they can be reviewed and committed alongside the code that caused them.

Do **not** run `git add` or `git commit`, and do not tell the user to run the sync again — the next edit session simply runs it once more when they're ready.
