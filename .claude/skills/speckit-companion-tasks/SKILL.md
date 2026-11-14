---
name: speckit-companion-tasks
description: Companion tasks — user-story phased task list
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.tasks.md
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
1. Read `.specify/feature.json` for the feature directory. The step's start is already stamped, above. Load `plan.md` and the feature spec (`spec.md`, or `<short-name>.spec.md` in a project written before this), both required, plus `data-model.md`, `contracts/`, and `research.md` if present.

2. Create `<feature_directory>/tasks.md` organized **by user story**, so each story can be implemented, tested, and delivered as an independent increment. Use the line format `- [ ] **T###** [P?] [US#] Description · exact/file/path`:
   - `[P]` marks a task that is **independent** of the others in its wave: a different file with no incomplete dependency, so it can be built in any order or in parallel.
   - `[US#]` maps the task to a user story from the spec for traceability.

3. **Group each phase's work into ordered waves, never a flat list**, so a reader sees at a glance *which tasks are independent* and *where work has to wait*:
   - A **wave** is a set of tasks that touch different files and don't depend on each other, so they can be built in any order. Head it with a line like `**Wave 1 — independent (different files):**` and tag each of its tasks `[P]`.
   - Between waves, write an explicit join line, `**⟶ Wait for Wave 1 to finish, then:**`, before the tasks that depend on the previous wave. Those form the next wave, or run singly.
   - A wave of one is fine: a single task, no `[P]`. Same-file or dependent tasks are **never** in the same wave. Group every genuinely independent task of the phase into one wave, so the dependency boundaries are honest.

4. Group the waves into phases, in this order:
   - **Phase 1: Setup**: project structure, config, and tooling prerequisites shared by everything.
   - **Phase 2: Foundational**: core infrastructure that BLOCKS all stories (shared models/types, providers, routing, persistence). No user-story work begins until this phase is done.
   - **Phase 3 onward: one phase per user story**, in priority order (P1 first = the MVP slice). For each story: a `### Tests` block with one task per requirement the story covers, each naming the requirement and the exact condition it states ("after a reload", "while offline") and testing it through the public surface (the rendered screen, the CLI, the API), never by reading storage or a store, written to fail first; then `### Implementation` laid out as waves (foundation and models first, then the independent components/UI wave, then the integration wave), then a **Checkpoint** line stating the story is now independently functional and testable.
   - **Final phase: Polish**: cross-cutting cleanup, docs, and validation against the spec's Success Criteria. It includes one task that runs the test and lint suites against the Success Criteria, unless a hook under `commands.implement.hooks.after.implement-exec` in `.specify/companion.yml` carries `owns: validation` (any other hook there does not count): then write `- [ ] **T###** Validate against Success Criteria — owned by the project's post-implement validation hook (no separate suite run)` instead, so the suites run in exactly one place.

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

**Output**: `<feature_directory>/tasks.md` organized by user story into dependency-ordered phases, each phase laid out as explicit waves with join points.
<!-- /speckit-companion:node tasks-doc -->
<!-- /speckit-companion:phase author -->
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

**The next step is `implement`.** Dispatch `speckit.companion.implement <feature_dir>`.

<!-- speckit-companion:part self-advance -->
## After this step

Inside a Companion auto run, or when the input says `workflow engine step`, the orchestrator decides what runs next: skip this. Otherwise, once this step's work is done, read `.specify/extensions/companion/commands/reference/self-advance.md` and follow it.
<!-- /speckit-companion:part self-advance -->

**Pin the workflow identity in the same call that closes the step.** Record that this spec runs the **Companion** workflow, so the next dispatch is a Companion command and not a stock one. The shared writer defaults `workflow` to `speckit`, so without this the footer advance dispatches the stock successor. `--set` writes a plain field and appends no history, so it rides along with `--advance`:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step <this step> --advance --by ai --set workflow=companion
```

Idempotent, and a required deterministic write. Skip only if `python3` is genuinely unavailable. This replaces the bare `--advance` the timing rules describe: run one or the other, never both.
<!-- /speckit-companion:node handoff -->
<!-- /speckit-companion:phase wrap-up -->

<!-- speckit-companion:part orchestrator -->
## Node hooks

A project can attach its own work around this command's nodes in `.specify/companion.yml`. **Only if that file declares `commands.<this-command>.hooks`**, read `.specify/extensions/companion/commands/reference/node-hooks.md` and follow it. Otherwise there are no hooks: skip this and run the command as written.
<!-- /speckit-companion:part orchestrator -->