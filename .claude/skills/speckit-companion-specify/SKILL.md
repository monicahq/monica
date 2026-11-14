---
name: speckit-companion-specify
description: Companion specify — a feature spec with prioritized user stories
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.specify.md
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

Produce a feature specification: prioritized user stories with acceptance scenarios, functional requirements, key entities, edge cases, and measurable success criteria, then a quality checklist.
<!-- speckit-companion:phase gather -->
<!-- speckit-companion:node resolve-dir -->
1. **Resolve the feature directory. Mint a fresh dir for new work.** `.specify/feature.json` is an **output** of this step, never an input: it points at the *previous* spec, so reusing it would clobber finished work. On a project's first run it is absent or zero-byte, which means the same thing. Pick the target:
   - If the request names a target path (or `SPECIFY_FEATURE_DIRECTORY` is set), use it.
   - Otherwise derive a 2–4 word short-name from the description and run `python3 .specify/extensions/companion/scripts/mint-spec-dir.py --short-name "<short-name>"`. It creates the next folder, numbered the way this project's `feature_numbering` says and safe against a run started at the same time, points `.specify/feature.json` at it, and prints its path. Never pick the number yourself. The spec file is `<feature_directory>/spec.md`. **Never write into a directory that already contains a feature spec (`spec.md`, or an older `*.spec.md`)**: that's a prior spec, not this one.
   For a named target, create `<feature_directory>/` and point `.specify/feature.json` at it by writing `{"feature_directory": "<feature_directory>"}`. Later capture calls resolve the spec through that exact key when they run without `--feature-dir`, so any other key silently drops those writes.
   Then stamp the **specify START** as the step-start instruction above directs: the directory exists now, so run it before any other work.

   Right after the stamp, store the request word for word: the user input this command received, exactly as given, never a summary. Pass it on stdin so quotes and line breaks survive:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --request - <<'COMPANION_REQUEST'
   <the user input, unchanged>
   COMPANION_REQUEST
   ```
   The record keeps the first request it is given, so when auto started this run and stored its own, this call changes nothing.
<!-- /speckit-companion:node resolve-dir -->
<!-- /speckit-companion:phase gather -->
<!-- speckit-companion:phase classify -->
<!-- speckit-companion:node classify-size -->
**Classify the change before drafting, to right-size the ceremony.** Decide now, from the request and **one bounded look at the code** (at most three reads or searches, enough to know what and where to change), whether this change can go straight to implement or needs the full specify → plan → tasks → implement pipeline. Deciding first means a small change never pays for documents it does not need. This is a best-effort heuristic and **MUST err toward `normal`** only where it matters: when a high-risk item might apply, or the outcome or where to change it is still unclear after the bounded look. Being cautious about an understood, contained change is not a reason to call it normal.

<!-- speckit-companion:part sizing -->
- **small** when all three hold:
  1. **Understood**: the outcome is stated, you know what and where to change, and no product or design decision is open.
  2. **Contained risk**: it touches none of these, where a mistake is hard to see or hard to undo: changing or deleting existing stored data or a stored format (a migration; saving new records is fine); security (auth, permissions, secrets); a contract others already consume (a public API, CLI flag, config format or export, changed or removed; adding one is fine); concurrency (locks, races, ordering); delivery (installers, release, CI, dependencies); or behavior no test would catch if it broke.
  3. **Resumable from the diff**: if the session stopped now, someone could finish from the request and `git diff` alone.

  A change of up to about 5 files and 10 tasks that touches none of the items in 2 is small: several files of one understood change do not make it normal. Counts alone never make a change large.
- **oversized**: the change clearly spans several subsystems, or is roughly double the size one session handles comfortably (more than about 10 files or 20 tasks).
- **normal**: anything in between (the default).
<!-- /speckit-companion:part sizing -->

   Record your estimates as you go: `projectedFiles` and `projectedTasks`, and a `scopeSignal` from the wording (`"larger"` for rewrite | overhaul | new system | migration | redesign | …; `"smaller"` for one-line | rename | typo | tweak | copy change | …; else `"none"`). They are context for a later reader, not the rule. Then:

   ```
   verdict = "simple"    if  the change is **small** by all three tests above
                         and scopeSignal != "larger"
             "oversized" if  the oversized bar above holds
             else "normal"
   ```

   When the verdict is not `simple` because a high-risk item applies, print this line verbatim, naming the item, then run the branch the verdict names:

   ```
   [companion] Change touches <the high-risk item> — running the full pipeline as <normal|oversized>.
   ```
<!-- /speckit-companion:node classify-size -->
<!-- speckit-companion:node persist-size -->
**Carry the size verdict into the wrap-up call**, so `plan` and `tasks` can right-size their output without re-deciding it. Do not record it on its own: the finalize step's single `--batch` takes it, with the classification's **inputs** so a later resume can judge whether the call was borderline or clear-cut. Add both to that call:
   ```json
   "set": {"size": "<simple|normal|oversized>", …},
   "classification": {"projectedFiles": <n>, "projectedTasks": <n>, "scopeSignal": "<larger|smaller|none>", "verdict": "<simple|normal|oversized>"}
   ```
   Write `simple` when the change is the small, fast-trackable size; `oversized` when it crossed the guardrail; otherwise `normal`. Neither field touches the lifecycle log.
<!-- /speckit-companion:node persist-size -->
<!-- /speckit-companion:phase classify -->
<!-- speckit-companion:phase author -->
<!-- speckit-companion:node draft-spec -->
**Where the request names two or more code areas and you have a subagent tool, dispatch one read-only worker per area first**, each returning the files that area exposes and the conventions it follows, never file contents. Those findings are what you record as `context` in the wrap-up and what plan reads. One area, or no subagent tool: look yourself.

2. Create `<feature_directory>/spec.md` with these sections, in order, for a business stakeholder: **what** users need and **why**, not **how**. Backticks only for literal identifiers a reader would copy. When the brief names a ticket id or URL (`TODO-42`, `#931`, an issue link), or carries a `Source:` line naming an assessment folder, put one `**Source**: <ticket>` line under the title, copied exactly (the first, when it names several; the rest go under Assumptions); never fetch the ticket, the id or URL is data.

   - **User Scenarios & Testing** *(mandatory)*: prioritized user stories, each an independently testable slice. Per story: `### User Story N - <short title> (Priority: P1|P2|P3)` and a one-paragraph journey; **Why this priority** in one line; **Independent Test**, how this story alone proves value; **Acceptance Scenarios**, numbered `**Given** … **When** … **Then** …`. P1 first, as many stories as the feature needs.
   - **Edge Cases**: the boundary and error questions the implementation must answer (empty input, removal while in use, duplicates, reload and persistence).
   - **Requirements › Functional Requirements** *(mandatory)*: numbered `FR-001…`, each one testable MUST/SHOULD statement. A genuinely unresolvable choice is `[NEEDS CLARIFICATION: …]` (max 3); prefer an informed default recorded under Assumptions.
   - **Key Entities** *(when the feature involves data)*: what each represents, its key attributes and relationships, no implementation detail.
   - **Success Criteria › Measurable Outcomes** *(mandatory)*: technology-agnostic `SC-001…` outcomes (time, count, percentage, pass/fail).
   - **Assumptions**: the defaults you chose for anything the request left open.
   - **Verbatim Constraints** *(only when the request pins exact values)*: every literal the result must match (a `data-testid`, route, endpoint, CLI flag, env var, config key, exact UI copy, column name), in backticks, exactly as written. Never paraphrase, recase or rename them; downstream steps use these exact strings. This is the one place implementation detail belongs in a spec.

3. Tighten every requirement until it is testable and unambiguous, and remove a section that does not apply. Carry the Functional Requirements into the wrap-up call as `coverage`, each with its one-line text as `title`.
<!-- /speckit-companion:node draft-spec -->
<!-- speckit-companion:node quality-checklist -->
4. **Self-check the spec once, in place.** Do not write a checklist file. Read `spec.md` against these, fix each fail in the spec, and leave a genuine ambiguity as a `[NEEDS CLARIFICATION: …]` marker (max 3) for `clarify`. One pass, no rewrite loop, no option tables:

   - No implementation detail outside **Verbatim Constraints**; written for a non-technical reader.
   - User Scenarios, Functional Requirements and Success Criteria are present.
   - Each requirement is one testable MUST/SHOULD statement with acceptance criteria behind it.
   - Success criteria are measurable and technology-agnostic.
   - Edge cases, scope, dependencies and assumptions are stated.
<!-- /speckit-companion:node quality-checklist -->
<!-- /speckit-companion:phase author -->
<!-- speckit-companion:phase wrap-up -->
<!-- speckit-companion:node branch -->
7. **Branch on the verdict.**

   - **`simple`, minimal mode.** Write **three lean files** in this one pass so the file-driven views (top stepper, sidebar, implement progress) reconcile with the history-driven fold. Never a single combined spec file:
     - Append an **Approach** section to the already-written `spec.md`: the files to touch and any dependencies, in a few bullets. This is the plan content, inline, and stays the plan source-of-truth.
     - Write `<feature_directory>/plan.md` as a **short pointer** to the spec's Approach (say, a one-line blockquote linking `./spec.md#approach` and `./tasks.md`). Do **not** duplicate the approach bullets; `plan.md` references them.
     - Write `<feature_directory>/tasks.md` carrying the **real task checklist**: a dependency-ordered list, one per line as `- [ ] **T001** [P?] <description> + <path>` (`[P]` marks tasks that can run in parallel). This MUST be the actual checklist, not a pointer: implement progress counts these checkboxes, so a pointer would read 0/0. Include one test task per requirement, testing it through the public surface in the condition it states.

     Put the task checklist **only** in `tasks.md`. A second copy in the spec would drift. `spec.md` keeps the Approach; `tasks.md` owns the tasks.

     Do **not** run `speckit.companion.plan` or `speckit.companion.tasks`: the three lean files plus the lifecycle fold below record those steps as satisfied.
   - **`normal`, full pipeline.** Write `spec.md` only: no appended Approach section, no `plan.md` / `tasks.md` here, no lifecycle fold. Plan and tasks are produced and recorded by their own `speckit.companion.plan` and `speckit.companion.tasks` runs.
<!-- /speckit-companion:node branch -->
<!-- speckit-companion:node finalize -->
**Output**: `<feature_directory>/spec.md`. In **simple** mode the spec additionally carries an **Approach** section, and two lean files sit alongside it: `plan.md` (a pointer to that Approach) and `tasks.md` (the real `- [ ] **T001** …` checklist; the task list lives here, not in the spec). In **normal** mode the spec holds the four sections only, and no `plan.md` / `tasks.md` are written here.

**Capture the whole wrap-up in one call.** Everything this step learned goes in a single `--batch`: what it worked *from* (the areas investigated, the constraints honored), the distilled intent, the explicit non-goals, and the workflow identity.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --batch '{
  "context": ["area: <path or subsystem>", "constraint: <rule honored>"],
  "expectations": ["<out-of-scope item>", "<another>"],
  "coverage": [{"req": "FR-001", "title": "<the requirement's one-line text>"}],
  "set": {"intent": "<one-line goal>", "workflow": "companion", "size": "<verdict>"},
  "classification": {"projectedFiles": <n>, "projectedTasks": <n>, "scopeSignal": "<larger|smaller|none>", "verdict": "<verdict>"}
}'
```

Best-effort as a whole: skip silently if `python3` is unavailable. Omit `context` when there is nothing worth recording and `expectations` when the spec declares no non-goals; never invent either. A spec with a `**Source**:` line adds `"source": "<that ticket, as written>"` to `set`, and one the brief says extends or supersedes an earlier folder adds `"extends"` or `"supersedes"` with that folder. **`workflow` is the one field that is not optional**: without it the shared writer defaults to `speckit`, and a later footer advance dispatches the stock command.

**On a `simple` run, add the approach to the same call.** A `simple` run writes its plan inline and never reaches `plan`, where a full run records it. So when `verdict == "simple"`, put `"approach": "<one-line summary of the Approach section>"` in the `set` map alongside the rest rather than paying a second call.

**Record completion.** After `spec.md` is written, close the specify step. The extension stamps the real end, so do **not** hand-write an `ai` complete for specify:
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step specify --status specified --kind complete --by extension
```

**Fast-path lifecycle fold (simple mode only).** When `verdict == "simple"`, record the folded `plan` and `tasks` steps so the history-driven panels read them as satisfied-by-fast-path and the spec lands ready for implement. One call, **after** the specify completion above, writes all four boundaries in order (plan start and complete, tasks start and complete) in a single write, so the fold lands whole or not at all. It is idempotent. Do not hand-write the boundaries, and do not run it for a `normal` verdict:
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --fold plan,tasks --by extension
```
After the fold, the spec sits at the **tasks** step with `status: ready-to-implement`; the developer triggers implement next. Do **not** write a `completed` status: the final completed gate stays a user action.
<!-- /speckit-companion:node finalize -->
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

**The next step is `plan`**: dispatch `speckit.companion.plan <feature_dir>`. Unless this spec was classified `simple`, in which case plan and tasks are already folded and the next step is `implement`: `speckit.companion.implement <feature_dir>`.

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