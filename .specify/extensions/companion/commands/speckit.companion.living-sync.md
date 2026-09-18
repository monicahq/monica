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

<!-- speckit-companion:part smallest-thing -->
## The smallest thing that works

**Before building anything, stop at the first rung that holds:** does it need to exist at all; does this codebase already have it; does the standard library, the platform, or an installed dependency do it; can it be one line; only then, the minimum code that works. Fix the cause where every caller passes through, not the symptom one caller reported. Delete rather than add, boring rather than clever: no interface with one implementation, no factory for one product, no scaffolding for later.

**The same test governs what you write.** A section nobody acts on is removed, not filled in. No requirement for what a type or a test already enforces. A third scenario has to cover a failure the first two miss.

**Write it the way you would say it.** One idea per sentence. No em-dashes: a full stop, a comma or a colon says it. Say what happens, not what the system "shall be capable of". Never a section that exists to say "N/A": remove it instead.

**Never simplify away** validation at a trust boundary, error handling that prevents data loss, security, accessibility, or anything the spec asks for. **A corner cut on purpose** carries `// simplified: <ceiling>, <what to do when it binds>` in the code and one `concerns` entry in this step's capture.
<!-- /speckit-companion:part smallest-thing -->

## Execution

### 1. Compute the sync plan

Run the drift detector in working-tree mode from the repository root:

```bash
python3 .specify/extensions/companion/scripts/drift.py --working --json
```

This is the same engine `speckit.companion.living-drift` uses — resolver membership, exempt globs, per-capability baselines, skip reasons — with `--working` widening each capability's changed set to the working tree (baseline→worktree diff plus untracked files). Its JSON output **is** the sync plan; do not regroup the files yourself with ad-hoc git commands.

Read the result:

- `enabled: false` → report `Living specs are off in this repo; nothing to sync.` and stop (success).
- `checked: 0` → nothing could be examined; report the `skipped` list with each reason and stop (success).
- Otherwise the plan is `capabilities[]`: each entry with a non-empty `drifted` list is a capability to sync (`name`, its spec at `spec`, and the changed files in `drifted[].file`); entries with `drifted: []` are already in sync and are left untouched.

### 2. Update each affected capability — every one, no hand-picking

For **each** capability with drifted files, edit its spec file (the `spec` path) in place, scoped to **that capability's** `drifted[].file` list:

> The capability has drifted — the code it describes changed since the spec was last committed (working-tree changes included). **UPDATE, do not regenerate**: keep every requirement, clarification, and acceptance scenario already written, and revise only what the changed files require. Read the listed changed files, work out what behavior was added, changed, or removed, and reflect exactly that. A file deleted from the working tree means its behavior was **removed** — reflect the removal rather than describing the file as if it still existed. Never rewrite untouched sections, never reorder requirements, and never flatten hand-written detail into a fresh draft.

**Widen each updated requirement's `touches` marker.** A requirement you revised now also describes the files that caused the revision, so its marker must say so — otherwise a later run narrowing on markers would skip the very requirement this change just made relevant. On the line immediately under the heading:

```markdown
### Pages delegate their chrome to a layout primitive
<!-- touches: src/layout/**, src/pages/shell.tsx -->
```

**Widen, never narrow.** Write the union of what the marker already named and the changed files you folded in. A requirement that used to describe a file it no longer touches keeps claiming it until someone edits the marker by hand — that costs a run one extra requirement, where narrowing could cost it a needed one. A requirement carrying no marker is read by every run, so leaving one unmarked is always safe; add one only when the changed files genuinely tell you what that requirement is about.

**Remove what the code no longer has.** When the changed files no longer contain what a requirement describes, the requirement is not "stale" — it is gone, and it is deleted from the spec, not softened. A sync that only ever adds produces a spec that is half history. Likewise a `// simplified:` comment that has disappeared from the code takes its `## Known limits` line with it, and a new one adds its line.

Work through the capabilities one at a time. If one capability's update fails (unreadable file, unresolvable content), warn, skip it, and continue with the rest — one bad capability never blocks the others.

### 3. Handle the skipped capabilities honestly

Report every entry in the plan's `skipped` list with its reason, verbatim. In particular:

- `spec.md not yet committed` — the capability has no committed baseline to diff against. Do **not** draft or redraft its spec here; that is bootstrap work, and `speckit.companion.living-adopt` owns it. Say so.
- Git/shallow-clone reasons — nothing to do but state them.

### 4. Report — and leave the edits uncommitted

End with a short report:

- **Synced** — each updated capability, with how many changed files were folded in.
- **Skipped** — each skipped capability and its reason.
- A closing note that the spec edits are **left uncommitted on purpose** so they can be reviewed and committed alongside the code that caused them.

Do **not** run `git add` or `git commit`, and do not tell the user to run the sync again — the next edit session simply runs it once more when they're ready.
