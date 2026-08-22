---
description: "Companion auto — run the whole pipeline hands-off (specify → plan → tasks → implement → mark-complete), no pauses"
---

## User Input

```text
$ARGUMENTS
```

## Outline

Run the **entire** Companion pipeline end-to-end and unattended. Walk every step in order — specify → plan → tasks → implement → mark-complete — dispatching the same per-step `/speckit.companion.*` commands, never pausing for approval in between, and finish the spec at `status: completed`.
1. **Resolve the feature directory — mint a fresh dir for new work.** Auto is a fresh-spec entry point, exactly like specify. `.specify/feature.json` is an **output**, not an input to reuse: it points at the *previous* spec (frequently already completed), so reusing it would clobber finished work. Pick the target:
   - If the request explicitly names a target path (or `SPECIFY_FEATURE_DIRECTORY` is set), use it.
   - Otherwise create the next numbered dir: scan `specs/` for the highest `NNN-…` prefix, derive a 2–4 word short-name from the description, and use `specs/<NNN+1>-<short-name>/`. **Never write into a directory that already contains a `spec.md`** — that's a stale pointer to a prior spec, not this feature.
   Create `<feature_directory>/`, point `.specify/feature.json` at it, then record the **specify START** so the step's duration begins now (the script stamps the real clock — do not hand-write this):
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step specify --status specifying --kind start --by extension
   ```

## Run the pipeline — every step, no pauses

Run the full Companion pipeline by **invoking each per-step command for real**, in order, without pausing for approval between them. You are the **conductor, not the author**: each step's behavior is defined by its own command body — do **not** write the spec, plan, design docs, task list, or code yourself from scratch. Invoke the command and let *it* do the step the way it's defined.

**This is the rule that makes auto faithful.** A standalone `/speckit.companion.tasks` run produces a size-classified spec, a slim plan with its design artifacts (`research.md`, `data-model.md`, `contracts/`), and a wave-structured task list — because those behaviors live *inside* each command. If you improvise the artifacts here instead of invoking the commands, auto silently drops all of that (no sizing, no design docs, a flat task list) and stops matching the manual flow. So: **invoke, don't reproduce.**

1. **Mark the run unattended.** This run has no human watching it. Set `unattended: true` so project checkpoint hooks record-and-continue instead of asking (see the unattended convention below) — write it into `.spec-context.json`:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --set unattended=true
   ```
   Carry `unattended` forward to every step you dispatch.

2. **Invoke each command in order — actually run it, don't re-enact it.** Use your command/skill invocation tool (the same `/speckit.companion.*` command a person would type) for each step, waiting for its full work to finish before starting the next. Each command does its *own* size-classification, artifact generation, and capture — your job is only to call them in sequence and not stop:
   - `/speckit.companion.specify <feature description>` — runs the real specify command: classifies size, writes the full spec, persists the size.
   - `/speckit.companion.plan` — runs the real plan command: the slim plan **plus** `research.md`, `data-model.md`, and `contracts/` (right-sized by the recorded size).
   - `/speckit.companion.tasks` — runs the real tasks command: the wave-structured, dependency-ordered task list.
   - `/speckit.companion.implement` — runs the real implement command: executes the tasks and journals each finish.
   - `/speckit.companion.mark-complete` — the terminal step that writes `status: completed`.
   If your host has no way to invoke another command mid-session, fall back to following each command's body faithfully (read it and do exactly what it specifies — same artifacts, same sizing, same structure); never substitute a quicker improvised version.

3. **Do not pause at review gates.** Where the manual flow would stop and wait for a person at a `gate` (review-spec, review-plan, …), auto instead **records the checkpoint and continues**. Background hooks still fire and review/PR hooks still run — only the human pause is skipped. This is the one behavioral difference from a manual run.

4. **End at `completed`.** mark-complete writes `completed` only through `write-context.py --mark-complete`, which refuses unless the spec is already `implemented`. Run it last so the spec lands at the end of the Active → Completed lifecycle. Never introduce a second completed-writer.

5. **Degrade gracefully on a one-shot environment.** Auto needs an agent that keeps acting after each step finishes. If your environment runs one command and then stops (a plain / one-shot terminal), you cannot chain the steps yourself: run the first step, record its progress, and stop. The run stays valid and resumable — the remaining steps are triggered the normal one-step-at-a-time way (by the developer or the companion panel). No error; auto simply behaves like the manual flow there.
<!-- speckit-companion:part timing -->
## Timing — keep `.spec-context.json` honest

These rules apply to every Companion profile command. The extension records lifecycle timing with its own scripts wherever it can; these rules keep anything you append consistent with that and accurate for any dispatcher (terminal, IDE chat, or the GUI). The model is **finish-only**: each task and each substep records a *single* finish event, and its duration is the gap to the previous finish (or the step's start). Never a `start`+`complete` pair for a task or substep — a pair stamped at one instant is what produces `0s` ticks and bursts.

- **Never hand-edit `.spec-context.json`.** Record every finish by **running the writer script**, never by editing the JSON file yourself — a hand-authored edit is what corrupts the file (a duplicated `status` key). The script stamps the real clock, writes atomically, and is idempotent. The commands below are the only way you touch timing.
- **Self-close — clarify and analyze only.** When your own work for **clarify or analyze** ends, record the step finish (feature dir from `.specify/feature.json`):

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --step <this step> --finish --by ai
  ```

  `--finish` appends a single step-level complete and touches **nothing else** (it leaves `status`/`currentStep` to the lifecycle hooks). Do NOT self-close **specify, plan, tasks, or implement**: for each of those steps the extension stamps both boundaries (start and complete) itself — specify and implement from their own command bodies, plan and tasks from a body-recorded start plus the after-step hook's completion. A step-level `ai` complete there would land first, and because the completion append is idempotent (first writer wins), it would permanently block the hook's extension-stamped close and leave the step's duration untrusted.
- **Substeps — one finish each, via the script.** For each substep boundary (plan: `research`, `design`; tasks: `generate`), the moment that substep ends, run:

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --step <step> --substep <name> --finish --by ai
  ```

  One call per substep, each stamped with its own real clock at the moment it finishes — never two substeps in one batch, never a separate `start`. The delta between consecutive finishes is each substep's duration.
- **Implement — finishing a task *is* logging it (finish-only).** Recording a task's finish is the **closing action of that task**, done the instant its work is complete and before you start the next one — not a bookkeeping pass you batch at the end of a phase. The closing action is a single append (feature dir from `.specify/feature.json`):

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --task <TaskID> --kind complete --by ai --did "<one-line summary of what this task did>" --files "<comma,separated,files,touched>" --append
  ```

  `--append` writes **one line** to `.spec-context.events.jsonl` and does **not** read or rewrite the shared `.spec-context.json`, so it never hits the "read the file first" retry and **parallel workers can each append their own finish at the same time without contending** — the line carries its own real timestamp (`date -u` is stamped by the script). The `--did`/`--files` flags ride along so the Activity panel's Tasks card is populated from the script. **Do NOT hand-edit the `- [ ]` checkbox in `tasks.md`** — the script owns it: materialize flips it to `- [x]` from your appended finish, so a fanned-out subagent only appends and never touches the shared `tasks.md`. Do NOT hand-author per-task JSON and do NOT write a per-task `start`.

  Then **fold the appended lines into `.spec-context.json` — per task, the moment each finish lands.** The fold is the second half of the task's closing action, run by the **MAIN agent only**, in the foreground, one task at a time — for your own task right after its append, and for a fanned-out worker's task the moment its result returns:

  ```bash
  python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_dir> --materialize
  ```

  `--materialize` is the one read-modify-write: it folds the finishes into the panel **and checks off the matching `tasks.md` boxes** for every journaled task, idempotently (re-folding never double-counts). The panel only sees folded finishes — the append log is not watched — so folding per task is what makes progress advance task by task instead of jumping in end-of-wave bursts. Workers never run `--materialize` (that would put two writers on the shared file); they only append, and the MAIN agent serializes every fold. Run it once more at each wave join as a backstop, and the end-of-step hook materializes anything left and fills any task you didn't journal. What's trustworthy here is the **per-task summary** (`did`/`files`) and the order tasks completed, plus the **step-level** start→complete span, which the scripts stamp exactly. The per-task *timestamps* are best-effort — they reflect when each finish was recorded, not a precisely measured duration; that's fine, the summaries are the point.
- **Never write the next step's start.** Only the next command appends the next step's start entry; writing it here makes the viewer render a phantom "Generating <next>…".
<!-- /speckit-companion:part timing -->

<!-- speckit-companion:part unattended -->
## Unattended — the "don't pause" signal

This run is **unattended**: a human is not watching it and cannot answer a prompt. The orchestrator records this by setting `unattended: true` in the dispatched prompt and in `.spec-context.json`, and every step you dispatch carries it forward.

What `unattended: true` means for hooks:

- **Checkpoint `prompt` hooks read it.** A project checkpoint hook ("Continue / Fix / Stop") is authored to check the flag: *if `unattended`, record the checkpoint and continue; otherwise ask the human to proceed.* The hook stays declarative — it does not need to know it is in auto, only that the run is unattended. A hook may still log one line such as `[hook] checkpoint recorded, continuing (unattended)`.
- **Background hooks still fire.** A `background: true` hook (tests, builds, notifications) runs exactly as it would in a manual run — unattended skips the *human pause*, not the side-effects.
- **Review / PR hooks still run.** Anything that produces an artifact or a review still happens; only the wait-for-a-person gate is bypassed.

If a project has no checkpoint hooks, `unattended: true` simply has nothing to act on — set it anyway so any hook added later inherits the contract.
<!-- /speckit-companion:part unattended -->

<!-- speckit-companion:part orchestrator -->
## Node hooks — run the project's `before`/`after` inserts

This command is assembled from ordered **nodes**. A project can attach its own work at the boundary *before* or *after* any node by declaring it in `.specify/companion.yml`. You are the runtime: read that file (if present) and run those hooks at the right moments. Like the rest of the pipeline, this must **never fail the host command** — degrade and continue.

**Find the hooks for this command.** Look up `commands.<this-command>.hooks` in `.specify/companion.yml`. It has two anchors, `before` and `after`, each keyed by a node id from this command's order. Run a node's `before` hooks immediately before that node's work, and its `after` hooks immediately after. When several hooks sit at one anchor, run them **top to bottom, in declared order**.

**Hook types:**

- `{ type: command, run: "<shell>" }` — run the shell command with your terminal/Bash tool, then continue. *If you have no terminal tool* (some chat-only providers), do not pretend to: report the command you would have run and continue.
- `{ type: prompt, text: "<instruction>" }` — treat the text as an inline instruction and act on it before moving on.
- `{ type: node, ref: <id> }` — read `.specify/companion/nodes/<id>.md` and carry out its body as if it were part of this command.

**Background hooks.** Any hook may add `background: true`. Kick it off and continue the pipeline immediately without waiting for it to finish — it must not hold the spec prisoner. Use it for slow, independent side-effects (a test run, a build, a notification): for a `command`, launch it detached (e.g. append `&` or use `nohup … &`); for a `node`/`prompt`, do its work without blocking the next step. Report its result whenever it lands, but never block on it. **Do not** mark a `background` hook on anything that writes `.spec-context.json` (the timing/capture calls): those are fast already and run a read-modify-write on the shared file, so two of them racing in the background can lose an update. Background is for side-effects, not bookkeeping.

**Failure handling (never abort the host command):**

- **No `.specify/companion.yml`** → there are no hooks; run the command exactly as written. Do not warn.
- **The file is malformed / unparseable** → ignore it, note one short warning, and run the shipped command unchanged.
- **A hook is anchored to a node that isn't in this run's order** (e.g. a recipe dropped it) → warn once and skip that anchor's hooks.
- **A `type: node` hook's `ref` file is missing** → this is a real misconfiguration: report it clearly and stop before doing damage, rather than silently skipping.

If a hook's own work fails (a `command` exits non-zero, a `node` can't complete), report it and — unless the failure clearly makes the rest unsafe — continue the pipeline. The host command's own output is never blocked by a hook.
<!-- /speckit-companion:part orchestrator -->
