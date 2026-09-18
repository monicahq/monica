---
description: "Companion implement — execute tasks.md in dependency order, then mark complete"
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

Execute `tasks.md` phase by phase in dependency order. Each phase is laid out as ordered **waves** split by `⟶ Wait …` join lines: a dependency map where tasks within a wave are independent and a `⟶ Wait` marks where the next tasks depend on what came before. Setup, the foundational phase and polish are built inline, wave by wave, stopping at each `⟶ Wait` line until the wave above is done; each user-story phase goes to its own worker wherever a subagent tool exists, and inline is the fallback for a host that cannot dispatch. Each task's finish is logged as it completes; then mark the spec complete.
<!-- speckit-companion:phase execute -->
<!-- speckit-companion:node implement-exec -->
1. Read `.specify/feature.json` for the feature directory. Load `<feature_directory>/tasks.md`, `plan.md`, and the feature spec (`<short-name>.spec.md`, or `spec.md` in a project written before this), plus `data-model.md` and `contracts/` if present. The step's start is already stamped, above.

2. Work `tasks.md` **phase by phase, in dependency order**: **Setup**, then **Foundational** (which blocks every story), then each **user-story** phase in priority order (P1 first), then **Polish**. `tasks.md` lays each phase out as ordered **waves** separated by `**⟶ Wait …**` join lines. The waves are a **dependency map**: tasks inside one wave are independent of each other, so any order is safe, and a `⟶ Wait` line marks where the next tasks depend on everything above it. **Execute wave by wave, in order, and stop at each `⟶ Wait` line until the wave above is done.** Halt on a failed task and report the cause.

3. **Dispatch one worker per user-story phase that owns five or more files. If you have a subagent tool, Claude Code's `Agent`/`Task` tool or your host's equivalent, you use it there.** Setup, Foundational and Polish always stay with you: Setup is trivial, Foundational blocks every story, Polish is cross-cutting. Never fan out per *task*: the startup costs more than the task.

   **Two tests decide it, in order.** First: did specify, plan and tasks already run in this same session? If they did not, the reading is not spent and every phase at or above the threshold is dispatched. Nothing you opened while orienting counts: reading `tasks.md`, `plan.md`, the spec, or a couple of source files to fix your conventions is not carrying a phase, and a phase whose files you have *partly* seen still counts as unread. Only when the whole pipeline ran in front of you is the reading genuinely spent, and then you build every phase inline and say so.

   Second, count the phase's own files line. A worker pays the same startup whatever it is handed, and below about five files that startup is the whole cost: measured across ten replays of two features, fanning out thin phases bought no correctness and no wall-clock at roughly twice the price, while a feature whose phases ran to six and eight files saved three minutes. Build a phase under the threshold inline, in phase order, and say which phases you dispatched and which you kept.

   **Read each story phase's files line, `Files:` or `Files owned by this phase:`. That is its ownership.** The tasks step gave every file one owner, so the story phases are disjoint by construction and you dispatch them all together. Two phases naming the same file is a defect in the task list: say so in your summary, and run those two one after another rather than together. Give each worker its phase's task lines, that user story from `spec.md`, the plan's Structure Decision, and **its own living-spec slice**, `resolve-spec-paths.py --changed <that phase's files> --requirements-for --follow-aligns --json`, so it carries the requirements about the files it touches and none of yours. The flag adds a rule from another capability that constrains this phase without living in it: the worker is writing the guarded code and is the last one who can honour it. Then ask it to read what it needs, write the code **and that story's tests**, run **only the test files its phase owns** (the full suite runs once, at the end), and return a distilled result only: what it built, the files it touched, and any test still failing. A worker must never return file contents.

   ```bash
   # the worker, per task it finishes: append only, never fold
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --task <TaskID> --kind complete --by ai --did "<one line>" --files "<files>" --append
   # you, the moment each worker's result returns
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --materialize
   ```

   Folding is a read-modify-write on the shared file, so two folders at once race. **Workers only ever append, and you do every fold**, in the foreground, one at a time.

4. **Only when you have no subagent tool at all, build the waves yourself, and close each task as you finish it**, the moment its work is done, never batched at the end of a wave:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --close-task <TaskID> --by ai --did "<one line>" --files "<files>"
   ```
   `--close-task` appends the finish and folds it in one call: the panel updates and the task's `tasks.md` box is checked. Never hand-edit the checkbox. This path is for hosts that cannot dispatch, not a choice.

5. **At each join line, check the workers' claims before crossing it.** A worker's report names files it touched and tests it ran; confirm the files exist and the test files are on disk before its result becomes the next phase's input. A claim that does not check out gets one re-run with the specific correction, and a second failure stops the step rather than building on it.

   Then reconcile. Hand the type-check and the lint to one worker and take back only the verdict and the failing names. Fix any seam drift, such as a worker that changed an interface another assumed. Then run `--materialize` once more as a backstop: it is idempotent, and it catches any finish whose fold was missed. `tasks.md` is owned only through `--materialize`.

6. **Run the project's own checks before you call this done.** Validating against the spec's **Functional Requirements** and **Success Criteria** by reading is not validation. Run the suite and the type-check or build the project actually uses, read from its `package.json` scripts, `Makefile`, or the repo's own instructions, and do not invent a command: a test you wrote and never executed is a guess about your own code.

   - **A test you authored that fails is your task, not a follow-up.** Fix it now.
   - **A pre-existing test your change invalidated is also yours.** Renaming what a component shows breaks the test asserting the old text. Updating it is part of the change, not scope creep.
   - **A test file that does not compile counts as failing.** Check the suite actually ran, not merely that the command exited.
   - **If you genuinely cannot run them**, because no test script exists or the environment forbids it, say so explicitly in the summary and record it as a concern below. Do not describe a read-through as though it were a run.

   **Then read your own diff and delete what it does not need**: a helper with one caller, a branch no input reaches, a wrapper that only forwards. Then report a short summary of what was built and anything left undone.

7. **Capture what was verified and decided** the moment validation ends (best-effort; JSON when you can, bare text when not; skip silently if `python3` is unavailable):
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step implement --batch '{
     "verified":   [{"what": "<check>", "command": "<cmd>", "result": "<outcome>", "warnings": ["<seen-and-dismissed>"]}],
     "decisions":  [{"decision": "<implementation choice>", "why": "<why>", "rejected": "<alternative>"}],
     "concerns":   [{"note": "<friction, residual risk, or a `// simplified:` ceiling you left in the code>", "step": "implement"}],
     "coverage":   [{"req": "FR-001", "tests": "<path.test.ts::case,other.test.ts>"}],
     "step_summary": {"summary": "<what shipped in one line>"},
     "last_action": "<final breadcrumb, e.g. all tasks done, 18/18 tests pass>"
   }'
   ```

   **One call, not one per item.** `--batch` takes the whole volley as a single JSON object and applies each writer additively, so the shared context file is read and rewritten once instead of once per entry. Emit one `--batch`. Include only the keys you actually have: an empty list is not the same as an absent one, and on a clean run `concerns` is genuinely absent.

   Record a check that can be run with `--verify-run "<what>::<command>"`: it runs the command and keeps the exit code, rather than taking your word for it. Every suite, build, lint and script goes that way. `--verified` stays for what genuinely cannot be run — a manual pass, a judgement — and reads in the viewer as your account rather than as evidence, which is what it is. Never write a `--verified` describing a command you ran: that is the case `--verify-run` exists for, and a typed result is indistinguishable from an imagined one. If a check could not be run at all, record that as a `--concern` naming what was skipped and why, and do **not** record a `--verified` for it.

   One `--verify-run` per runnable check and one `--verified` per judgement (a manual pass, a warning you saw and judged benign), one `--coverage-req … --tests …` per requirement a test covers, one `--decision` per genuine implementation choice. Record `--concern` only for real friction; on a clean run record none.

**Output**: working changes per `tasks.md`, with completed tasks checked off.
<!-- /speckit-companion:node implement-exec -->
<!-- /speckit-companion:phase execute -->
<!-- speckit-companion:phase wrap-up -->
<!-- speckit-companion:node complete -->
8. **Mark the spec complete.** Once every task in `tasks.md` is checked off and the work validates, finish the lifecycle so the spec lands at `completed` instead of stopping at `implemented`.

   **"Validates" means the project's own checks ran and passed.** A spec MUST NOT be marked complete over a failing suite the run introduced: fix it, or leave the spec at `implemented` and say why. Where the checks genuinely could not be run, record that as a concern before completing, so the state reads "finished, unverified" instead of implying "finished, verified". Run from the repository root; the feature directory resolves on its own:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --mark-complete --by ai --set workflow=companion
   ```
   The `--set` pins which workflow finished the spec in the same write, so a mid-run join keeps Companion dispatch. This is the only sanctioned writer of `completed`: it closes the implement step and promotes an `implemented` spec, or an `implementing` one whose tasks are all checked, straight to `completed`, keeping `currentStep` at `implement`. Best-effort and idempotent: if `python3` is unavailable, warn and skip without failing the host command, and a spec already `completed` is left untouched. Running it when the spec-kit workflow engine already called the same path is harmless.

   - **Account for every loaded capability first: a delta or an explicit skip, never silence.** Before folding, read `livingSpecs.loaded` in this feature's `.spec-context.json`. An absent key or an empty list means nothing was loaded and there is nothing to account for; skip to the fold. Go through **every** name in that list, and give each exactly one of two outcomes. For a loaded capability whose *behavior* this feature actually changed, append a delta block to this feature's `spec.md` capturing the real new or changed requirement, marked with that capability's name so the fold routes it to the right spec:
     ```markdown
     ## ADDED Requirements
     <!-- capability: <name> -->

     ### <the new capability requirement, as a testable statement>

     #### Scenario: <name>
     - **WHEN** <trigger>
     - **THEN** <observable outcome>
     ```
     **A file no loaded capability claims belongs to a capability that does not exist yet.** Before writing any block, run the resolver on the files you changed: `python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --changed <files> --json`. A file with no match is new behaviour with no home. Do not route it to the nearest capability you happened to load. Give it a block of its own with a new name, `<!-- capability: <name> -->`, where the name is a thing a person can now do, said out loud, never a directory. Register it before the fold, with the directories you touched as its match: `python3 .specify/extensions/companion/scripts/register-capability.py --name <name> --match '<dir>/**'`. The fold then creates the spec from your block. Say in your summary that a capability appeared and why.

     Pick the verb by whether the requirement heading already exists in the capability's living spec (`capabilities/<name>/<name>.spec.md`). A requirement that is **not already there** goes under `## ADDED Requirements`, even if it revises the same behavior area. Reserve `## MODIFIED Requirements` for changing the body of a requirement whose heading is already in the living spec; the heading must match an existing one for the edit to replace it in place. **Read the existing headings before choosing:** a new heading that says what an existing one says in other words is that requirement, changed, and belongs under MODIFIED with the existing heading. A `// simplified:` ceiling you left in the code is **not** a delta entry: inside a delta block every `###` is a requirement heading, so a "Known limits" heading there folds into the capability as a requirement. Record ceilings as `concerns` in this step's capture instead, and let `living-sync` place them. Use `## REMOVED Requirements` when you deleted one, and `## RENAMED Requirements` (`### Old heading -> New heading`) for a rename. Write one block per changed capability, each with its own `<!-- capability: <name> -->` marker: several marked blocks fan out, each capability spec receiving only its own requirements. Never invent requirements to pad the list, and add a third scenario to an existing requirement only when it covers a failure the first two miss, saying which in the scenario name.

     For a loaded capability whose behavior this feature did **not** change, one you merely read for context, record an explicit skip. One call per untouched capability:
     ```bash
     python3 .specify/extensions/companion/scripts/write-context.py --living-spec-skip "<name>: <one-line reason it wasn't changed>"
     ```
     By the end, every name in `livingSpecs.loaded` is accounted for by a delta block or a recorded skip. A capability that is neither is a hole the fold flags.

   - **Fold living-spec deltas (opt-in, best-effort).** After the completion write, fold the deltas you just authored into the durable living spec, OpenSpec's "archive" step:
     ```bash
     python3 .specify/extensions/companion/scripts/write-context.py --fold-living-spec --by ai
     ```
     It parses the feature spec for `## ADDED / MODIFIED / REMOVED / RENAMED Requirements` blocks and applies each to the resolved `capabilities/<name>/<name>.spec.md`: the changed-files-matched capability for unmarked blocks, and every `<!-- capability: <name> -->`-marked capability for the rest. Opt-in (it only acts when `livingSpecs.enabled: true`), a clean no-op when there is no delta block, idempotent on re-run, and it records the synced names onto `livingSpecs.synced`. Never fails the host command.
<!-- /speckit-companion:node complete -->
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

<!-- speckit-companion:part self-advance -->
## Self-advance: hand off to the next step

This is one step in the Companion pipeline. How the run continues depends on the environment you are running in; do not invoke a separate headless/deterministic run command for the everyday flow.

- **On an agentic CLI that keeps acting after a step finishes:** once this step's work is complete, dispatch the next step's `speckit.companion.*` command and keep going. The order is fixed and this step's own handoff names its successor, so there is no file to open to find out.
- **Pause at every review gate, and name the command that continues.** Where the workflow marks a `gate` (e.g. review-spec, review-plan), stop and wait for approval rather than running past it. When you stop, **name the next command literally**: "approve and run `speckit.companion.plan <feature_dir>`", not "approve to move to plan". Only continue once the gate is approved.
- **Nothing follows implement.** Implement's own final node writes `completed` through `write-context.py --mark-complete`, so the spec is already finished when this step ends. Do not dispatch `speckit.companion.mark-complete` afterwards: it is the manual recovery command and the workflow engine's terminal step, not a step a run adds for itself. There is exactly one writer of `completed`; never introduce a second.
- **Degrade gracefully on a one-shot environment.** If your environment runs one step and then stops, the handoff simply does not fire: finish this step, record its progress, and stop. The run stays valid and resumable, and the next step is triggered manually, by the developer or the companion panel. Completion likewise stays a manual action there.
<!-- /speckit-companion:part self-advance -->
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
