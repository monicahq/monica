---
description: "Companion auto — run the whole pipeline hands-off (specify → plan → tasks → implement → mark-complete), no pauses"
---

## User Input

```text
$ARGUMENTS
```

<!-- speckit-companion:part step-start -->
## Record this step's start: before anything else runs

A step's recorded window has to contain the work it claims. Stamping the start partway down the body leaves the extension hooks, and any node above the stamp, outside the window the step later reports. So this is the first instruction in the command, ahead of the hooks.

Let `<step>` be this command's phase and `<status>` its in-progress status: `specify`/`specifying`, `plan`/`planning`, `tasks`/`tasking`, `implement`/`implementing`.

**Which feature directory this step stamps against decides when it stamps.**

- **A step that mints its own feature directory**, meaning any fresh-spec entry point such as `specify` or `auto`, has nothing to stamp against yet. `.specify/feature.json` is this step's *output*: it still points at the **previous** spec, so stamping now would write this run's status onto finished work. Resolve the directory first, then stamp the instant it exists, before any other work in the step.
- **Every other step** reads the feature directory it was given, from the invocation or from `.specify/feature.json`, which by then points at this spec. Stamp immediately, before the extension hooks and before any node.

In both cases the call is the same:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step <step> --status <status> --kind start --by extension
```

Add `--at "<dispatch time>"` when the dispatcher printed one; otherwise the script stamps now. Two things keep this honest:

- **Run it, never hand-write it.** The script stamps the real clock and writes atomically. A hand-authored entry in `.spec-context.json` is what corrupts the file.
- **A second start is refused, not reconciled.** History is append-only, so if the extension already seeded this step's start, this call appends nothing and the earlier timestamp stands. Running it is always safe; skipping it loses the window.
<!-- /speckit-companion:part step-start -->

<!-- speckit-companion:part command-spelling -->
## Name every command the way this project registers it

Commands are named in dot form throughout this body, `speckit.companion.plan`, because that is their canonical id, and without a leading slash, because the spelling a host actually registers is not always this one. Claude Code installs `/speckit-companion-plan`. Look at how the commands are installed in this project, under the agent's own commands or skills directory, and use that spelling every time you name one to the developer or dispatch one yourself. A dotted name typed into a host that registered dashes resolves to nothing at all.
<!-- /speckit-companion:part command-spelling -->

## Outline

Run the **entire** Companion pipeline end-to-end and unattended. Walk every step in order, specify → plan → tasks → implement → mark-complete, dispatching the same per-step `speckit.companion.*` commands, never pausing for approval in between, and finish the spec at `status: completed`.
<!-- speckit-companion:phase gather -->
<!-- speckit-companion:node resolve-dir -->
1. **Resolve the feature directory — mint a fresh dir for new work.** Auto is a fresh-spec entry point, exactly like specify. `.specify/feature.json` is an **output**, not an input to reuse: it points at the *previous* spec (frequently already completed), so reusing it would clobber finished work. Pick the target:
   - If the request explicitly names a target path (or `SPECIFY_FEATURE_DIRECTORY` is set), use it.
   - Otherwise create the next numbered dir: scan `specs/` for the highest `NNN-…` prefix, derive a 2–4 word short-name from the description, and use `specs/<NNN+1>-<short-name>/`. The spec file is `<feature_directory>/<short-name>.spec.md` (for a named target, `<short-name>` is its directory name without the numeric prefix). **Never write into a directory that already contains a feature spec (`*.spec.md`, or an older `spec.md`)** — that's a stale pointer to a prior spec, not this feature.
   Create `<feature_directory>/`, then point `.specify/feature.json` at it by writing `{"feature_directory": "<feature_directory>"}` — that exact key is what the later capture calls resolve the spec through when they run without `--feature-dir`, so any other key silently drops those writes. Then stamp the **specify START** as the step-start instruction above directs — the directory now exists, so this is the moment it says to run it, before any other work.
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
   - `speckit.companion.plan` — runs the real plan command: the slim plan **plus** `research.md`, `data-model.md`, and `contracts/` (right-sized by the recorded size).
   - `speckit.companion.tasks` — runs the real tasks command: the wave-structured, dependency-ordered task list.
   - `speckit.companion.implement` — runs the real implement command: executes the tasks and journals each finish.
   If your host has no way to invoke another command mid-session, fall back to following each command's body faithfully (read it and do exactly what it specifies — same artifacts, same sizing, same structure); never substitute a quicker improvised version.

3. **Do not pause at review gates.** Where the manual flow would stop and wait for a person at a `gate` (review-spec, review-plan, …), auto instead **records the checkpoint and continues**. Background hooks still fire and review/PR hooks still run — only the human pause is skipped. This is the one behavioral difference from a manual run.

4. **End at `completed`.** Implement's own final node writes it, through `write-context.py --mark-complete`, which refuses unless the spec is already `implemented`. So the run ends when implement ends — do not dispatch `speckit.companion.mark-complete` as a fifth step. There is exactly one writer of `completed`; never introduce a second.

5. **Degrade gracefully on a one-shot environment.** Auto needs an agent that keeps acting after each step finishes. If your environment runs one command and then stops (a plain / one-shot terminal), you cannot chain the steps yourself: run the first step, record its progress, and stop. The run stays valid and resumable — the remaining steps are triggered the normal one-step-at-a-time way (by the developer or the companion panel). No error; auto simply behaves like the manual flow there.
<!-- /speckit-companion:node orchestrate -->
<!-- /speckit-companion:phase orchestrate -->
<!-- speckit-companion:phase wrap-up -->
<!-- speckit-companion:node handoff -->
<!-- speckit-companion:part timing -->
## Timing: keep `.spec-context.json` honest

Record every boundary by **running the writer script**. Never edit `.spec-context.json` yourself; a hand-authored edit is what corrupts the file. The model is **finish-only**: one finish per task and per substep, its duration the gap back to the previous finish. Never a `start`+`complete` pair for either, which stamps a `0s` tick and measures nothing.

- **Close your own step**, as the last thing you do, after emitting any mandatory after-hook block:

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --step <this step> --advance --by ai
  ```

  `--advance` appends the step's complete and flips `status` in one atomic write. It is idempotent and first-writer-wins, so it changes nothing when the after-hook already closed the step, and it is the only thing that closes the step when that hook was printed rather than dispatched. Run it every time, with two exceptions: **clarify** and **analyze** use `--finish`, which records a boundary without owning a status; **implement** runs neither, because its own final node writes `completed` and closes the step in the same write.

- **One finish per substep, the moment it ends.** Plan records `research` and `design`, tasks records `generate`. Never two in one batch, never a separate start.

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --step <step> --substep <name> --finish --by ai
  ```

- **Closing a task is that task's last action, not a bookkeeping pass you batch later.** Feature dir from `.specify/feature.json`:

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --close-task <TaskID> --by ai --did "<one line>" --files "<files>"
  ```

  One call appends the finish with its own real clock, folds it into the panel, and flips that task's box in `tasks.md`. Never hand-edit that box or hand-author per-task JSON, and never write a per-task start. Re-closing is safe. **Batching is a defect the doctor catches**: it names any cluster of finishes stamped seconds apart, because those timestamps record when the batch was written, and history is append-only so it cannot be repaired afterwards. Trust the per-task summaries and their order; the timestamps are best-effort.

  **A fanned-out worker appends only**, because folding is a read-modify-write and two folders contend:

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --task <TaskID> --kind complete --by ai --did "<one line>" --files "<files>" --append
  ```

  The MAIN agent folds each returned result with `--materialize`, one at a time, and once more at a wave join as a backstop.

- **Never write the next step's start.** The next command owns it. Writing it here renders a phantom "Generating <next>…".
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
## Node hooks: run the project's `before`/`after` inserts

This command is assembled from ordered **nodes**. A project can attach its own work before or after any node by declaring it in `.specify/companion.yml`. You are the runtime: read that file if it is there and run those hooks at the right moments. Like the rest of the pipeline, this must **never fail the host command**. Degrade and continue.

**Find the hooks for this command.** An absent or empty `.specify/companion.yml` means no hooks: skip silently, and never warn. Look up `commands.<this-command>.hooks`. It has two anchors, `before` and `after`, each keyed by a node id from this command's order. Run a node's `before` hooks immediately before that node's work, and its `after` hooks immediately after. When several hooks sit at one anchor, run them **top to bottom, in declared order**.

**Hook types:**

- `{ type: command, run: "<shell>" }`: run the shell command with your terminal/Bash tool, then continue. *If you have no terminal tool* (some chat-only providers), don't pretend to: report the command you would have run and continue.
- `{ type: prompt, text: "<instruction>" }`: treat the text as an inline instruction and act on it before moving on.
- `{ type: node, ref: <id> }`: read `.specify/companion/nodes/<id>.md` and carry out its body as if it were part of this command.

**Background hooks.** Any hook may add `background: true`. Kick it off and continue immediately, without waiting for it to finish. Use it for slow, independent side-effects such as a test run, a build or a notification: for a `command`, launch it detached (e.g. append `&` or use `nohup … &`); for a `node`/`prompt`, do its work without blocking the next step. Report its result whenever it lands, but never block on it. **Do not** mark `background` on anything that writes `.spec-context.json`, meaning the timing and capture calls: those run a read-modify-write on a shared file, so two racing in the background can lose an update. Background is for side-effects, not bookkeeping.

**Failure handling (never abort the host command):**

- **No `.specify/companion.yml`** → there are no hooks; run the command exactly as written. Do not warn.
- **The file is malformed or unparseable** → ignore it, note one short warning, and run the shipped command unchanged.
- **A hook is anchored to a node that isn't in this run's order** (e.g. a recipe dropped it) → warn once and skip that anchor's hooks.
- **A `type: node` hook's `ref` file is missing** → a real misconfiguration: report it clearly and stop before doing damage, rather than silently skipping.

If a hook's own work fails (a `command` exits non-zero, a `node` can't complete), report it and continue the pipeline, unless the failure clearly makes the rest unsafe. A hook never blocks the host command's own output.
<!-- /speckit-companion:part orchestrator -->
