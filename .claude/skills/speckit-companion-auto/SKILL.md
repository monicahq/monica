---
name: speckit-companion-auto
description: Companion auto — run the whole pipeline hands-off (specify → plan → tasks → implement → converge), no pauses
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.auto.md
---

## User Input

```text
$ARGUMENTS
```

<!-- speckit-companion:part step-start -->
## Record this step's start, before anything else runs

Let `<step>`/`<status>` be this command's phase and in-progress status: `specify`/`specifying`, `plan`/`planning`, `tasks`/`tasking`, `implement`/`implementing`. A step that mints its own feature directory (`specify`, `auto`) resolves it first, because `.specify/feature.json` still points at the previous spec; every other step stamps at once, before any hook or node:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step <step> --status <status> --kind start --by extension
```

Add `--at "<dispatch time>"` when the dispatcher printed one. Run it, never hand-write it. A second start is refused, so running it is always safe.
<!-- /speckit-companion:part step-start -->

<!-- speckit-companion:part command-spelling -->
## Name every command the way this project registers it

Commands are named in dot form throughout this body, `speckit.companion.plan`, because that is their canonical id, and without a leading slash, because the spelling a host actually registers is not always this one. Claude Code installs `/speckit-companion-plan`. Look at how the commands are installed in this project, under the agent's own commands or skills directory, and use that spelling every time you name one to the developer or dispatch one yourself. A dotted name typed into a host that registered dashes resolves to nothing at all.
<!-- /speckit-companion:part command-spelling -->

## Outline

Run the **entire** Companion pipeline end-to-end and unattended. Walk every step in order, specify → plan → tasks → implement → converge, dispatching the same per-step `speckit.companion.*` commands, never pausing for approval in between, and finish the spec at `status: completed`.
<!-- speckit-companion:phase gather -->
<!-- speckit-companion:node resolve-dir -->
1. **Resolve the feature directory — mint a fresh dir for new work.** Auto is a fresh-spec entry point, exactly like specify. `.specify/feature.json` is an **output**, not an input to reuse: it points at the *previous* spec (frequently already completed), so reusing it would clobber finished work. Pick the target:
   - If the request explicitly names a target path (or `SPECIFY_FEATURE_DIRECTORY` is set), use it.
   - Otherwise derive a 2–4 word short-name from the description and run `python3 .specify/extensions/companion/scripts/mint-spec-dir.py --short-name "<short-name>"`. It creates the next folder, numbered the way this project's `feature_numbering` says and safe against a run started at the same time, points `.specify/feature.json` at it, and prints its path. Never pick the number yourself. The spec file is `<feature_directory>/spec.md`. **Never write into a directory that already contains a feature spec (`spec.md`, or an older `*.spec.md`)** — that's a stale pointer to a prior spec, not this feature.
   For a named target, create `<feature_directory>/` and point `.specify/feature.json` at it by writing `{"feature_directory": "<feature_directory>"}` — that exact key is what the later capture calls resolve the spec through when they run without `--feature-dir`, so any other key silently drops those writes.
   Then stamp the **specify START** as the step-start instruction above directs — the directory now exists, so this is the moment it says to run it, before any other work.

   Right after the stamp, store the request word for word: the user input this command received, exactly as given, never a summary. Pass it on stdin so quotes and line breaks survive:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --request - <<'COMPANION_REQUEST'
   <the user input, unchanged>
   COMPANION_REQUEST
   ```
   The record keeps the first request it is given, so the description you hand specify later never replaces it.
<!-- /speckit-companion:node resolve-dir -->
<!-- /speckit-companion:phase gather -->
<!-- speckit-companion:phase orchestrate -->
<!-- speckit-companion:node orchestrate -->

## Run the pipeline — every step, no pauses

Run the full Companion pipeline by **invoking each per-step command for real**, in order, without pausing for approval between them. You are the **conductor, not the author**: each step's behavior is defined by its own command body — do **not** write the spec, plan, design docs, task list, or code yourself from scratch. Invoke the command and let *it* do the step the way it's defined.

**This is the rule that makes auto faithful.** A standalone `speckit.companion.tasks` run produces a size-classified spec, a slim plan with its design artifacts (`research.md`, `data-model.md`, `contracts/`), and a wave-structured task list — because those behaviors live *inside* each command. If you improvise the artifacts here instead of invoking the commands, auto silently drops all of that (no sizing, no design docs, a flat task list) and stops matching the manual flow. So: **invoke, don't reproduce.**

1. **Mark the run unattended.** This run has no human watching it. Set `unattended: true` so project checkpoint hooks record-and-continue instead of asking (see the unattended convention below) — write it into `.spec-context.json`:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --set unattended=true
   ```
   Carry `unattended` forward to every step you dispatch.

2. **Invoke each command in order — actually run it, don't re-enact it.** Use your command/skill invocation tool (the same `speckit.companion.*` command a person would type) for each step, waiting for its full work to finish before starting the next. Each command does its *own* size-classification, artifact generation, and capture — your job is only to call them in sequence and not stop:
   - `speckit.companion.specify <feature description>` — runs the real specify command: classifies size, writes the full spec, persists the size.
   - **Read the verdict before the next dispatch.** When specify returns, read `<feature_directory>/.spec-context.json`. If `size` is `simple`, or its `history` already holds a `tasks` complete, specify folded plan and tasks: the lean `plan.md` and `tasks.md` exist and both steps are recorded. Print `[companion] specify folded plan and tasks; going to implement`, skip the two commands below, and dispatch implement. A re-run of plan or tasks here would only overwrite the lean files and cost the time the fold saved. Any other verdict runs all three.
   - `speckit.companion.plan` — runs the real plan command: the slim plan **plus** `research.md`, `data-model.md`, and `contracts/` (right-sized by the recorded size).
   - `speckit.companion.tasks` — runs the real tasks command: the wave-structured, dependency-ordered task list.
   - `speckit.companion.implement` — runs the real implement command: executes the tasks and journals each finish.
   - `speckit.converge` — the stock converge command: it checks the build against the spec and either completes the spec or appends the unbuilt work as new tasks. Before each converge, run `python3 .specify/extensions/companion/scripts/write-context.py --hook start --step converge` yourself, so the pass is on record even when the host skips the before hook; it never opens a second pass.
   If your host has no way to invoke another command mid-session, fall back to following each command's body faithfully (read it and do exactly what it specifies — same artifacts, same sizing, same structure); never substitute a quicker improvised version.

3. **Do not pause at review gates.** Where the manual flow would stop and wait for a person at a `gate` (review-spec, review-plan, …), auto instead **records the checkpoint and continues**. Background hooks still fire and review/PR hooks still run — only the human pause is skipped. This is the one behavioral difference from a manual run.

4. **End when converge completes the spec.** After converge, read `<feature_directory>/.spec-context.json`. If `status` is `completed`, the run is done. If converge appended tasks and the status is still `implemented`, build them yourself once, following the implement instructions already in this session (do not invoke the implement command again, which reloads them), close each wave as implement does, then converge once more, checking only the tasks it appended. That is the one correction round: if the second converge still leaves open tasks, record one concern naming them and end at `implemented`. Never start a third pass. Do not dispatch `speckit.companion.mark-complete`. There is exactly one writer of `completed`: the after-converge hook; never introduce a second.

5. **Degrade gracefully on a one-shot environment.** Auto needs an agent that keeps acting after each step finishes. If your environment runs one command and then stops (a plain / one-shot terminal), you cannot chain the steps yourself: run the first step, record its progress, and stop. The run stays valid and resumable — the remaining steps are triggered the normal one-step-at-a-time way (by the developer or the companion panel). No error; auto simply behaves like the manual flow there.
<!-- /speckit-companion:node orchestrate -->
<!-- /speckit-companion:phase orchestrate -->
<!-- speckit-companion:phase wrap-up -->
<!-- speckit-companion:node handoff -->
<!-- speckit-companion:part timing -->
## Timing: keep `.spec-context.json` honest

Record every boundary by **running the writer script**, never by editing `.spec-context.json`. The model is **finish-only**: one finish per task and per substep, timed from the previous one; never a `start`+`complete` pair. **One shell call per moment**: writes that happen together go in one call chained with `&&`, because every separate call is a turn that re-reads the whole session. **In a single-quoted `--batch '{…}'`, write an apostrophe as `'`**, or pass `--batch -` with the JSON on stdin; never debug the quoting by reading the script.

- **Close your own step** as your last action, after any mandatory after-hook block. Specify already closed itself in its wrap-up; clarify and analyze use `--finish` instead. Implement ends at `implemented`; converge completes the spec.

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --step <this step> --advance --by ai
  ```

- **One finish per substep, the moment it ends, when the step runs on its own**; inside an auto run, skip them. Plan records `research` and `design`, tasks `generate`.

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --step <step> --substep <name> --finish --by ai
  ```

- **Close each wave the moment its last task is done**, in one call listing its tasks. It checks their boxes and times the wave as one unit. Never hand-edit a box; closing several waves at once is a defect the doctor reports.

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --close-task <T001,T002,…> --by ai --did "<one line>" --files "<files>"
  ```

  **A fanned-out worker appends only**, because two folders contend; the main agent folds each returned result with `--materialize`, one at a time:

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --task <TaskID> --kind complete --by ai --did "<one line>" --files "<files>" --append
  ```

- **Never write the next step's start.** The next command owns it.
<!-- /speckit-companion:part timing -->

<!-- speckit-companion:part unattended -->
## Unattended: the "don't pause" signal

This run is **unattended**: a human is not watching it and cannot answer a prompt. The orchestrator records this by setting `unattended: true` in the dispatched prompt and in `.spec-context.json`, and every step you dispatch carries it forward.

What `unattended: true` means for hooks:

- **Checkpoint `prompt` hooks read it.** A project checkpoint hook ("Continue / Fix / Stop") is authored to check the flag: *if `unattended`, record the checkpoint and continue; otherwise ask the human to proceed.* The hook stays declarative: it does not need to know it is in auto, only that the run is unattended. A hook may still log one line such as `[hook] checkpoint recorded, continuing (unattended)`.
- **Background hooks still fire.** A `background: true` hook (tests, builds, notifications) runs exactly as it would in a manual run. Unattended skips the *human pause*, not the side-effects.
- **Review / PR hooks still run.** Anything that produces an artifact or a review still happens; only the wait-for-a-person gate is bypassed.

If a project has no checkpoint hooks, `unattended: true` has nothing to act on. Set it anyway so any hook added later inherits the contract.
<!-- /speckit-companion:part unattended -->
<!-- /speckit-companion:node handoff -->
<!-- /speckit-companion:phase wrap-up -->

<!-- speckit-companion:part orchestrator -->
## Node hooks

A project can attach its own work around this command's nodes in `.specify/companion.yml`. **Only if that file declares `commands.<this-command>.hooks`**, read `.specify/extensions/companion/commands/reference/node-hooks.md` and follow it. Otherwise there are no hooks: skip this and run the command as written.
<!-- /speckit-companion:part orchestrator -->