---
description: "Companion tasks — user-story phased task list"
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

<!-- speckit-companion:part speckit-hooks -->
## Pre-Execution Checks: stock spec-kit extension hooks

Companion runs **on top of** stock spec-kit, so a project's installed spec-kit **extensions** (git, and any others registered in `.specify/extensions.yml`) must still fire on a Companion run exactly as they do on a stock `/speckit.*` run. That is separate from Companion's own node-hooks in `.specify/companion.yml`; both fire. Like the rest of the pipeline this must **never fail the host command**: anything missing or malformed is skipped silently.

Let `<step>` be this command's phase: `specify`, `plan`, `tasks`, or `implement`. Run the pass twice: `hooks.before_<step>` **now, before any of the work below**, and `hooks.after_<step>` once this command's work is fully reported, before handing off.

- **Read `.specify/extensions.yml`.** Absent, unparseable, or carrying no entries for that anchor: skip silently, there is nothing to run.
- **Skip a hook that is `enabled: false`** (no `enabled` field means enabled), **and any hook whose `extension` is `companion`**. Those exist so a stock run records its lifecycle, and this command records its own in its own body, so dispatching them would rewrite what this step just wrote. Every other extension's hooks fire as normal.
- **Leave `condition` to the HookExecutor.** A hook with no condition, or a null or empty one, is executable; one with a non-empty condition is skipped here and never evaluated by you.
- **Emit one block per executable hook.** An optional hook (`optional: true`):

  ```
  ## Extension Hooks

  **Optional Pre-Hook**: {extension}
  Command: `/{command}`
  Description: {description}

  Prompt: {prompt}
  To execute: `/{command}`
  ```

  A mandatory hook (`optional: false`) instead:

  ```
  ## Extension Hooks

  **Automatic Pre-Hook**: {extension}
  Executing: `/{command}`
  EXECUTE_COMMAND: {command}

  Wait for the result of the hook command before proceeding to the Outline.
  ```

  Those labels are the **before** pass's. In the **after** pass drop `Pre-` from the label and drop the closing wait line; there is nothing left to wait for.

For `specify`, branch creation is normally one of these `before_specify` hooks (the git extension); the spec directory and its files are always created by the command body itself.
<!-- /speckit-companion:part speckit-hooks -->

<!-- speckit-companion:part smallest-thing -->
## The smallest thing that works

**Before building anything, stop at the first rung that holds:** does it need to exist at all; does this codebase already have it; does the standard library, the platform, or an installed dependency do it; can it be one line; only then, the minimum code that works. Fix the cause where every caller passes through, not the symptom one caller reported. Delete rather than add, boring rather than clever: no interface with one implementation, no factory for one product, no scaffolding for later.

**The same test governs what you write.** A section nobody acts on is removed, not filled in. No requirement for what a type or a test already enforces. A third scenario has to cover a failure the first two miss.

**Write it the way you would say it.** One idea per sentence. No em-dashes: a full stop, a comma or a colon says it. Say what happens, not what the system "shall be capable of". Never a section that exists to say "N/A": remove it instead.

**Never simplify away** validation at a trust boundary, error handling that prevents data loss, security, accessibility, or anything the spec asks for. **A corner cut on purpose** carries `// simplified: <ceiling>, <what to do when it binds>` in the code and one `concerns` entry in this step's capture.
<!-- /speckit-companion:part smallest-thing -->

## Outline

Produce a dependency-ordered task list organized **by user story** into phases (Setup → Foundational → one phase per story → Polish), so each story is an independently testable increment.
<!-- speckit-companion:phase gather -->
<!-- speckit-companion:node size-budget -->
**Right-size this task list to the change.** Before drafting, read the recorded size from the spec's context: `.spec-context.json` → the `size` field (treat a missing value as `normal`). **Apply the budget to the step below, omitting anything it says to skip.**

- **`normal`**: produce the full phased task list exactly as the step describes. No trimming.
- **`oversized`**: produce the same full phased task list, and open it with a short **Scale note**, one or two sentences naming how many files and areas the change spans and what a reader should watch for. Size never trims here. An oversized change gets *more* signposting, not less.
- **`simple`**: a small change needs the tasks, not the ceremony. Produce a **lean** list:
  - **No baseline or setup task** for "run install/build to confirm green". That is not real work.
  - Group by phase still (Setup if any → Foundational → the work → Polish), but **drop the per-story `Goal` / `Independent Test` / `Checkpoint` blocks**. A small change ships in one pass, not as separate demoable slices.
  - End with a **one-line** dependency note (what blocks what), **not** the full "Dependencies & Execution Order" and "Parallel Opportunities" prose.
  - Keep every task line precise (the `T###`, the exact file, the requirement). Trim the framing, never the tasks.

This budget governs the step that follows. Where it would produce something the budget skips, omit it.
<!-- /speckit-companion:node size-budget -->
<!-- /speckit-companion:phase gather -->
<!-- speckit-companion:phase author -->
<!-- speckit-companion:node tasks-doc -->
1. Read `.specify/feature.json` for the feature directory. The step's start is already stamped, above. Load `plan.md` and the feature spec (`<short-name>.spec.md`, or `spec.md` in a project written before this), both required, plus `data-model.md`, `contracts/`, and `research.md` if present.

2. Create `<feature_directory>/tasks.md` organized **by user story**, so each story can be implemented, tested, and delivered as an independent increment. Use the line format `- [ ] **T###** [P?] [US#] Description · exact/file/path`:
   - `[P]` marks a task that is **independent** of the others in its wave: a different file with no incomplete dependency, so it can be built in any order or in parallel.
   - `[US#]` maps the task to a user story from the spec for traceability.

3. **Group each phase's work into ordered waves, never a flat list**, so a reader sees at a glance *which tasks are independent* and *where work has to wait*:
   - A **wave** is a set of tasks that touch different files and don't depend on each other, so they can be built in any order. Head it with a line like `**Wave 1 — independent (different files):**` and tag each of its tasks `[P]`.
   - Between waves, write an explicit join line, `**⟶ Wait for Wave 1 to finish, then:**`, before the tasks that depend on the previous wave. Those form the next wave, or run singly.
   - A wave of one is fine: a single task, no `[P]`. Same-file or dependent tasks are **never** in the same wave. Group every genuinely independent task of the phase into one wave, so the dependency boundaries are honest.
   This wave layout is the execution map implement reads: implement dispatches one worker per story phase, and the waves are the dependency order within it.

4. Group the waves into phases, in this order:
   - **Phase 1: Setup**: project structure, config, and tooling prerequisites shared by everything.
   - **Phase 2: Foundational**: core infrastructure that BLOCKS all stories (shared models/types, providers, routing, persistence). No user-story work begins until this phase is done.
   - **Phase 3 onward: one phase per user story**, in priority order (P1 first = the MVP slice). For each story: an optional `### Tests` block (include only when the spec or constitution asks for tests, and write them to fail first), then `### Implementation` laid out as waves (foundation and models first, then the independent components/UI wave, then the integration wave), then a **Checkpoint** line stating the story is now independently functional and testable.
   - **Final phase: Polish**: cross-cutting cleanup, docs, and validation against the spec's Success Criteria. **Single-owner validation:** by default this phase generates a task that runs the test and lint suites to validate against the Success Criteria. Skip that suite-run task ONLY when the project has explicitly handed validation to a post-implement hook: read `.specify/companion.yml` and look under `commands.implement.hooks.after.implement-exec` for a hook entry carrying the marker `owns: validation`. Presence of a hook is NOT the signal; review, PR, and deploy hooks share this same anchor, and only the `owns: validation` marker counts. When a marked hook is present, emit a deferring task (`- [ ] **T###** Validate against Success Criteria — owned by the project's post-implement validation hook (no separate suite run)`) instead of a second run. With no marked hook (the common case, or `companion.yml` absent or malformed), Polish owns validation and generates the suite-run task as usual. Either way the suites run in exactly one place.

5. **Every file has exactly one owner phase.** Before writing the list, walk the files the plan names and give each to one phase. A file two stories would both touch is either foundational, so it goes in Phase 2 and is built before any story starts, or it is split so each story owns its own file (`enqueue.service.ts` and `replay.service.ts`, not one service both edit). Open each story phase with one line, `Files: <the files it owns>`, and name the test files it owns under `### Tests`. Writing the list is one pass and never fans out, but **it is what lets implement fan out:** disjoint phases mean implement hands each story to its own worker, and one shared file means it cannot. Before you finish, grep your own list. A path appearing under two phases is the defect to fix here, not to discover there.

6. End with a **Dependencies & Execution Order** section: the phase dependencies (Setup → Foundational → stories → Polish) and a one-line restatement of each phase's waves (which wave blocks which). Each task names the concrete file it creates or edits.

7. **Capture the requirement→task map** so "which tasks cover FR-X?" is answerable from the context file (best-effort; skip silently if `python3` is unavailable, and the implement step fills each requirement's tests later). Carry each requirement's one-line text as its `title`, so the requirement is captured as readable content and not just an id:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step tasks --batch '{
     "coverage": [
       {"req": "FR-001", "title": "<the requirement one-line text>", "tasks": "T001,T004"},
       {"req": "FR-002", "title": "<…>", "tasks": "T005"}
     ],
     "step_summary": {"summary": "<task count + phase shape in one line>"}
   }'
   ```

   **One call, not one per item.** `--batch` takes the whole volley as a single JSON object and applies each writer additively, so the shared context file is read and rewritten once instead of once per entry. Emit one `--batch`.

**Output**: `<feature_directory>/tasks.md` organized by user story into dependency-ordered phases, each phase laid out as explicit waves with join points.
<!-- /speckit-companion:node tasks-doc -->
<!-- /speckit-companion:phase author -->
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

**The next step is `implement`.** Dispatch `speckit.companion.implement <feature_dir>`.

<!-- speckit-companion:part self-advance -->
## Self-advance: hand off to the next step

This is one step in the Companion pipeline. How the run continues depends on the environment you are running in; do not invoke a separate headless/deterministic run command for the everyday flow.

- **On an agentic CLI that keeps acting after a step finishes:** once this step's work is complete, dispatch the next step's `speckit.companion.*` command and keep going. The order is fixed and this step's own handoff names its successor, so there is no file to open to find out.
- **Pause at every review gate, and name the command that continues.** Where the workflow marks a `gate` (e.g. review-spec, review-plan), stop and wait for approval rather than running past it. When you stop, **name the next command literally**: "approve and run `speckit.companion.plan <feature_dir>`", not "approve to move to plan". Only continue once the gate is approved.
- **Nothing follows implement.** Implement's own final node writes `completed` through `write-context.py --mark-complete`, so the spec is already finished when this step ends. Do not dispatch `speckit.companion.mark-complete` afterwards: it is the manual recovery command and the workflow engine's terminal step, not a step a run adds for itself. There is exactly one writer of `completed`; never introduce a second.
- **Degrade gracefully on a one-shot environment.** If your environment runs one step and then stops, the handoff simply does not fire: finish this step, record its progress, and stop. The run stays valid and resumable, and the next step is triggered manually, by the developer or the companion panel. Completion likewise stays a manual action there.
<!-- /speckit-companion:part self-advance -->

**Pin the workflow identity in the same call that closes the step.** Record that this spec runs the **Companion** workflow, so the next dispatch is a Companion command and not a stock one. The shared writer defaults `workflow` to `speckit`, so without this the footer advance dispatches the stock successor. `--set` writes a plain field and appends no history, so it rides along with `--advance`:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step <this step> --advance --by ai --set workflow=companion
```

Idempotent, and a required deterministic write. Skip only if `python3` is genuinely unavailable. This replaces the bare `--advance` the timing rules describe: run one or the other, never both.
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
