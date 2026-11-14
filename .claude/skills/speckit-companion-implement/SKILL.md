---
name: speckit-companion-implement
description: Companion implement — execute tasks.md in dependency order, then converge
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.implement.md
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

<!-- speckit-companion:part speckit-hooks -->
## Stock spec-kit extension hooks

Only if `.specify/extensions.yml` lists entries under `hooks.before_<step>` or `hooks.after_<step>`, where `<step>` is this command's phase, read `.specify/extensions/companion/commands/reference/speckit-hooks.md` and follow it: the `before_` pass now, before any work below, and the `after_` pass once this command's work is reported. Otherwise skip this.
<!-- /speckit-companion:part speckit-hooks -->

<!-- speckit-companion:part concise -->
## The smallest thing that works

**Stop at the first rung that holds:** does it need to exist; does this codebase already have it; does the platform or an installed dependency do it; can it be one line; only then, the minimum code. Fix the cause where every caller passes through. No interface with one implementation, no factory for one product, no scaffolding for later. Lean governs what you build, not how work is split.

**Write it the way you would say it.** One idea per sentence, no em-dashes, no "shall be capable of", no section that says "N/A". Then reread and cut: the sentence that restates the one before, the example longer than its rule, the clause defending a choice nobody challenged.

**Never simplify away** validation at a trust boundary, error handling that prevents data loss, security, accessibility, or anything the spec asks for. **A corner cut on purpose** carries `// simplified: <ceiling>, <what to do when it binds>` in the code and one `concerns` entry in this step's capture.
<!-- /speckit-companion:part concise -->

## Outline

Execute `tasks.md` phase by phase in dependency order. Each phase is laid out as ordered **waves** split by `⟶ Wait …` join lines: a dependency map where tasks within a wave are independent and a `⟶ Wait` marks where the next tasks depend on what came before. Setup and polish are built inline, wave by wave, stopping at each `⟶ Wait` line until the wave above is done; Foundational waves of four or more tasks go to workers through `dispatch-briefs.py --waves`, and smaller ones stay inline; each user-story phase goes to its own worker wherever a subagent tool exists, and inline is the fallback for a host that cannot dispatch. Each task's finish is logged as it completes; implement then ends at `implemented` and `converge` completes the spec.
<!-- speckit-companion:phase execute -->
<!-- speckit-companion:node implement-exec -->
1. Read `.specify/feature.json` for the feature directory. Load `<feature_directory>/tasks.md`, `plan.md`, and the feature spec (`spec.md`, or `<short-name>.spec.md` in a project written before this), plus `data-model.md` and `contracts/` if present. The step's start is already stamped, above.

2. Work `tasks.md` **phase by phase, in dependency order**: **Setup**, then **Foundational** (which blocks every story), then each **user-story** phase in priority order (P1 first), then **Polish**. `tasks.md` lays each phase out as ordered **waves** separated by `**⟶ Wait …**` join lines. The waves are a **dependency map**: tasks inside one wave are independent of each other, so any order is safe, and a `⟶ Wait` line marks where the next tasks depend on everything above it. **Execute wave by wave, in order, and stop at each `⟶ Wait` line until the wave above is done.** Halt on a failed task and report the cause.

3. **Dispatch one worker per user-story phase that owns five or more files**, when you have a subagent tool (Claude Code's `Agent`/`Task` tool or your host's equivalent): read `.specify/extensions/companion/commands/reference/fan-out.md` first and follow it, including the check at each join line. Count the phase's own `Files:` line, and that alone decides it. It holds in an auto run too. Every other phase, Setup, Polish, and everything without a subagent tool, you build yourself. **In Foundational, before each wave, run `python3 .specify/extensions/companion/scripts/dispatch-briefs.py --feature-dir <feature_directory> --waves` and do exactly what it prints.** Setup and Polish stay with you.

4. **What you build yourself** (Setup, Polish, small phases and waves, or everything without a subagent tool), **close each wave with one call the moment its last task is done**, listing its tasks:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --close-task <T001,T002,…> --by ai --did "<one line for the wave>" --files "<files>"
   ```
   One call folds the finishes, checks the boxes and times the wave as one unit. Never hand-edit a checkbox.


6. **Run the project's own checks before you call this done.** Validating against the spec's **Functional Requirements** and **Success Criteria** by reading is not validation. Run the suite and the type-check or build the project actually uses, read from its `package.json` scripts, `Makefile`, or the repo's own instructions, and do not invent a command: a test you wrote and never executed is a guess about your own code.

   - **A test you authored that fails is your task, not a follow-up.** Fix it now.
   - **A pre-existing test your change invalidated is also yours.** Renaming what a component shows breaks the test asserting the old text. Updating it is part of the change, not scope creep.
   - **A test file that does not compile counts as failing.** Check the suite actually ran, not merely that the command exited.
   - **If you genuinely cannot run them**, because no test script exists or the environment forbids it, say so explicitly in the summary and record it as a concern below. Do not describe a read-through as though it were a run.

   **Then read your own diff and delete what it does not need**: a helper with one caller, a branch no input reaches, a wrapper that only forwards. Then report a short summary of what was built and anything left undone.

**Output**: working changes per `tasks.md`, with completed tasks checked off.
<!-- /speckit-companion:node implement-exec -->
<!-- speckit-companion:node record-verified -->
6b. **Prove each requested rule where a reader meets it, before you call it verified.** For every requirement, at least one test drives the app's public surface (the rendered screen, the CLI, the API) in the exact condition the rule names, and none passes by reading internal state such as storage, a store or a private module. "Survives a reload" means building the app again from what storage kept and reading the screen; "sends in order" means counting the calls the app makes. A rule with no such test gets one now; if it fails, the code changes.

   Then name the risk in one line of your summary, `Risk: <item> (<why>)` or `Risk: none`, against these items: stored data or a stored format, security, a contract others consume, concurrency or ordering, delivery or dependencies, or behavior no test would catch. Before writing `Risk: none`, check whether your change edits code that behavior the request did not mention also uses.

7. **Capture what was verified and decided in one call** the moment validation ends (best-effort; skip silently if `python3` is unavailable). Include only the keys you have; on a clean run `concerns` is absent:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step implement --batch '{
     "verified":   [{"what": "<judgement>", "result": "<outcome>"}],
     "decisions":  [{"decision": "<implementation choice>", "why": "<why>", "rejected": "<alternative>"}],
     "concerns":   [{"note": "<friction, residual risk, or a `// simplified:` ceiling>", "step": "implement"}],
     "coverage":   [{"req": "FR-001", "tests": "<path.test.ts::case>"}, {"req": "FR-004", "assumes": "<what it relies on without a test>"}],
     "step_summary": {"summary": "<what shipped in one line>"},
     "last_action": "<final breadcrumb>"
   }'; python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --verify-run "<what>::<command>" --verify-run "<what>::<command>"
   ```
   Every suite, build and lint goes through `--verify-run`, which runs the command and keeps its exit code. `verified` is only for what cannot be run, a manual pass or a judgement. A check you could not run is a `concerns` entry naming what was skipped; do **not** record a `--verified` for it.

8. **Set the review depth from the risk tier** in the same breath:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --risk
   ```
   It prints the tier the changed files call for; raise it with `--risk-raise <normal|high> "<reason>"` if you know more, never lower. Review the diff before it is committed at that depth: low is your own careful read, normal one independent review, high two. When the work becomes a pull request, paste `python3 .specify/extensions/companion/scripts/receipts.py --feature-dir <feature_directory>` into its body unedited.

9. **Mutation check, only when the project turned it on.** If `.specify/companion.yml` sets `mutation: { enabled: true }`, read `.specify/extensions/companion/commands/reference/mutation-check.md` and follow it. Otherwise skip this.
<!-- /speckit-companion:node record-verified -->
<!-- /speckit-companion:phase execute -->
<!-- speckit-companion:phase wrap-up -->
<!-- speckit-companion:node complete -->
10. **Close implement at `implemented`.** Once every task in `tasks.md` is checked off and the work validates, close the step. Implement ends at `implemented`; the next step, `converge`, is what completes the spec when it finds nothing left to build.

   **"Validates" means the project's own checks ran and passed.** A spec MUST NOT be marked complete over a failing suite the run introduced: fix it, or leave the work open and say why. Where the checks genuinely could not be run, record that as a concern before closing, so the state reads "finished, unverified" instead of implying "finished, verified". Run from the repository root; the feature directory resolves on its own:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step implement --advance --by ai --set workflow=companion
   ```
   The `--set` pins which workflow ran the spec in the same write, so a mid-run join keeps Companion dispatch. This closes the implement step and leaves the spec at `implemented`; it never writes `completed`. Best-effort and idempotent: if `python3` is unavailable, warn and skip without failing the host command.
<!-- /speckit-companion:node complete -->
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

**The next step is `converge`.** Dispatch `speckit.converge <feature_dir>`.

<!-- speckit-companion:part self-advance -->
## After this step

Inside a Companion auto run, or when the input says `workflow engine step`, the orchestrator decides what runs next: skip this. Otherwise, once this step's work is done, read `.specify/extensions/companion/commands/reference/self-advance.md` and follow it.
<!-- /speckit-companion:part self-advance -->
<!-- /speckit-companion:node handoff -->
<!-- /speckit-companion:phase wrap-up -->

<!-- speckit-companion:part orchestrator -->
## Node hooks

A project can attach its own work around this command's nodes in `.specify/companion.yml`. **Only if that file declares `commands.<this-command>.hooks`**, read `.specify/extensions/companion/commands/reference/node-hooks.md` and follow it. Otherwise there are no hooks: skip this and run the command as written.
<!-- /speckit-companion:part orchestrator -->