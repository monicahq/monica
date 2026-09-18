---
description: "Report on a spec's run health — unfinished steps, unjournaled tasks, step bleed, drift you can judge, a step that closed having verified nothing, a step that closed without the file it promised, and why completion did not land (read-only, retroactive, never halts)"
---

# Pipeline Doctor

Say what actually happened in a run — and where the record, the display, or the run's own claim is at fault. Everything here is **recomputed**, never read off a prior verdict: a run that claimed it was drift-clean gets checked against a fresh drift computation, not believed.

**Read-only.** It creates, modifies, and deletes nothing. **Never halts** — it always exits `0`, and a check that crashes becomes that check's skip reason while the rest still run. **Retroactive** — every core check derives from `.spec-context.json` and the spec's own documents, so it produces a meaningful verdict on a spec created long before this command existed.

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip:
  `[companion] Warning: python3 not detected; skipped doctor`.
  Do not fail the host command.

## Execution

Run the doctor from the repository root. With no argument it examines the active spec, resolved the same way every capture call resolves it:

```bash
python3 .specify/extensions/companion/scripts/doctor.py
```

Name a spec explicitly, or sweep every spec in the repository:

```bash
python3 .specify/extensions/companion/scripts/doctor.py --feature-dir specs/<NNN>-<slug>
python3 .specify/extensions/companion/scripts/doctor.py --all
```

Add `--json` for the machine-readable report (stable top-level keys: `checks`, `findings`, `bleed`, `drift`, `completion`, `chat`), and `--chat` for the deep transcript audit described below.

## What it checks

Each check reports whether it **ran**, was **skipped** (always with a reason), or is **not applicable**. A check that could not look never prints as clean — the summary counts checks, not just findings.

- **record** — steps that started and never finished, tasks checked off in `tasks.md` with no journal entry, task finishes clustered into one burst (journaling that was batched, so the per-task durations mean nothing), and steps closed by the wrong author.
- **triage** — the "status says one thing, the pipeline bar offers another" symptom, resolved into exactly one of two verdicts: *records disagree with each other* (a capture defect — the stepper is derived from `history[]`, so a missing step-level complete is why it will not advance) or *records are consistent* (look at the display).
- **bleed** — where one step did the next step's work: plan content in the spec, a task checklist in the plan, implementation code in the task list, one task list living in two documents, source files committed before implement started, and a pre-implement step that outlasted implement itself.
- **drift** — re-runs the drift computation and shows its work: which capability, which files, which commits. Every flag is classified **real**, **self-inflicted** (the only changes are records the companion writes during a run), **suspect baseline** (the comparison commit is not an ancestor of `HEAD`, or the change is a rename git can follow), or **unknown** (the baseline could not be reached — never reported as clean). A recorded drift-clean claim the recomputation contradicts is reported as a false claim, with both sides and their timestamps.
- **completion** — when a spec did not land as `completed`, which of four things happened: the write was refused (with the writer's own reason), it reported success and never arrived, it landed and the display disagrees, or completion was never attempted at all.
- **verification** — whether the run actually executed anything before implement closed. Running the project's own checks is an instruction with nothing observing it, so a run can write code, tick off a task naming a test, and finish having proven nothing. An implement step that closed with no executed check recorded against the run is reported. A spec that never reached implement reports **no record**, never a problem.
- **artifact** — whether each step left behind the file it declared it would write. Every author node names its output in `writes:`, and the build collects those into a manifest of what a run of this pipeline must produce; a step that quietly stops writing its document otherwise closes exactly like one that wrote it. Reported as a warning, never a gate: the manifest describes the pipeline as built today, and a spec on disk may have been produced by an earlier one. An artifact the size budget is allowed to fold away is not judged, and a step that produced none of what this pipeline declares reports **no record** — it ran some other pipeline.
- **template** — whether `tasks.md` still has the shape it was generated with: user-story phases containing waves, `⟶ Wait` join lines and checkpoints intact. A file whose story sections were renamed or flattened is reported with the offending headings named.
- **trace** — what the run self-trace recorded: capture calls that failed and why, call counts, payload sizes, and how many times each file was rewritten. Calls that could not resolve a spec at all land in the repo-level unattributed log and are reported here too, because a spec whose own trace is clean is not evidence that nothing broke while it was being built. The marker naming calls that succeeded but could not be written to the trace is read **before** the check decides a spec has no trace evidence — a run that could not write into its spec directory has no trace file at all, and that is precisely the run with something to report.
- **chat** *(only with `--chat`)* — reads the AI session transcript covering the run's recorded time window and explains causes: work tried and failed, work retried, and steps that stopped rather than failed. It surfaces claims the run made that the recomputation contradicts, and quantifies waste — narration, repeated commands, the same file rewritten over and over. Claude-first; on a provider that keeps no transcript it prints one line and exits successfully. The transcript format is not a stable contract, so treat this as a builder's tool, not a product promise.

## The self-trace

Every capture and drift call records itself to `specs/<NNN>/.trace.jsonl` — one line, success or failure, with the reason verbatim. This costs no extra call and adds nothing to any command body; the scripts the pipeline already runs write it from the inside. The file is size-capped, ignores itself on first write, and is read by nothing but this command. Deleting it is safe; a missing trace is a skipped check, never an error.

## Debug mode

Setting `debug: true` in `.specify/companion.yml` is read by the body renderers, but **the renderers are build-time tools and are not part of a release** — so on an installed project this switch currently does nothing. Treat it as a maintainer tool: from a source checkout, `python3 speckit-extension/scripts/assemble-nodes.py --debug` and `build-commands.py --debug` render the bodies with per-section timing instrumentation, and a plain rebuild removes it again. Never commit an instrumented body.

To instrument a run on an installed project today, attach the instruction as a node hook in your own `.specify/companion.yml` — that mechanism ships and takes effect on the next dispatched command. See the `debug-timing` hook in this repository's own config for the wording.

## Reporting

Report the doctor's output as it stands. Do **not** fix what it finds as part of running it — the doctor reports, and acting on a finding is a separate, deliberate decision. If it finds nothing, say so along with which checks were skipped and why, so "clean" is never mistaken for "did not look".
