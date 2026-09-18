---
description: "Companion specify — a feature spec with prioritized user stories"
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

Produce a feature specification: prioritized user stories with acceptance scenarios, functional requirements, key entities, edge cases, and measurable success criteria, then a quality checklist.
<!-- speckit-companion:phase gather -->
<!-- speckit-companion:node resolve-dir -->
1. **Resolve the feature directory. Mint a fresh dir for new work.** `.specify/feature.json` is an **output** of this step, never an input: it points at the *previous* spec, so reusing it would clobber finished work. On a project's first run it is absent or zero-byte, which means the same thing. Pick the target:
   - If the request names a target path (or `SPECIFY_FEATURE_DIRECTORY` is set), use it.
   - Otherwise create the next numbered dir: scan `specs/` for the highest `NNN-…` prefix, derive a 2–4 word short-name from the description, and use `specs/<NNN+1>-<short-name>/`. The spec file is `<feature_directory>/<short-name>.spec.md`. For a named target, `<short-name>` is its directory name without the numeric prefix. **Never write into a directory that already contains a feature spec (`*.spec.md`, or an older `spec.md`)**: that's a prior spec, not this one.
   Create `<feature_directory>/`, then point `.specify/feature.json` at it by writing `{"feature_directory": "<feature_directory>"}`. Later capture calls resolve the spec through that exact key when they run without `--feature-dir`, so any other key silently drops those writes. Then stamp the **specify START** as the step-start instruction above directs: the directory exists now, so run it before any other work.
<!-- /speckit-companion:node resolve-dir -->
<!-- speckit-companion:node load-living-specs -->
**Load living specs, so you arrive pre-briefed (best-effort, opt-in, read-only).** Before drafting, check whether this project keeps **living specs** for the areas this change touches, and fold them into your context. On any miss (no config, feature off, no resolver, no spec file) skip silently and draft as usual: this step must **never** fail or slow the command. It is strictly **read-only**. Never create or edit a `capabilities/<name>/<name>.spec.md` from here.

   - **Record deterministically first. Never hand-judge the gate.** Don't decide "is this project configured?" or "which capabilities apply?" yourself. Run the deterministic recorder with the files this change will touch. **Name that surface from the feature description, before you have opened anything.** A request always says where it lands: "drafts in the editor" is `src/pages/editor/`, "the article page" is `src/pages/article/`. A directory is enough, and being wrong costs nothing because membership globs are coarse and the resolver narrows from there. Skipping because you have not read any files yet is how the load silently never happens, and a run that skips it is a run with no brief. Skip only when the feature names no area you can guess at all. It reads the registry (`living-specs.yml`, or a legacy `livingSpecs` block in `.specify/companion.yml`), gates on `enabled`, runs the resolver, writes the matched capabilities leaf-first onto `livingSpecs.loaded`, and writes the `last_action` audit breadcrumb:
     ```bash
     python3 .specify/extensions/companion/scripts/record-living-specs.py --feature-dir <feature_directory> --changed <in-scope files…>
     ```
     It writes only additive `livingSpecs.loaded` plus the breadcrumb on `.spec-context.json`, never the lifecycle log, and exits 0 as a silent no-op when the feature is off, nothing matches, or the registry can't be read. Like every other capture call here, skip it silently if `python3` or the script is unavailable.
   - **Then read what it recorded, by requirement, leaf first.** Read `livingSpecs.loaded` back from `<feature_directory>/.spec-context.json`. If the key is absent or the list is empty, there is nothing to load; continue to the spec draft. Otherwise ask the resolver what each capability should contribute for these files:
     ```bash
     python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --changed <in-scope files…> --requirements-for --json
     ```
     Each entry comes back in the recorded order, most-specific first, with either `"whole": true`, meaning read the whole `spec` file, or `"whole": false` plus a `purpose` and the `requirements` to contribute. **Each requirement carries its `heading` and its full `body`**, the normative prose and its scenarios. **Read only what it names.** Skip any the resolver marked `"exists": false`.

     A requirement carrying no marker is always in the list. If the resolver is unavailable or the call fails, fall back to reading each `spec` path whole: the narrowing must never cost you the brief.

     The leaf capability is the **primary** frame for this change; a parent capability is the surrounding **context**. Honor both while drafting: they describe how the area already behaves.

   - **Honor the project's authored spec rules.** The same call carries a `rules` object. `rules.spec` is a short list of one-line house rules the project authored. Read **only** `rules.spec` here (`rules.plan` belongs to the plan step and must not leak into the draft) and treat each line as an instruction while writing the spec. An empty list is the normal case: say nothing about rules and draft as usual. These lines shape *how* the spec is written; they never add requirements or override anything in this command body.
<!-- /speckit-companion:node load-living-specs -->
<!-- /speckit-companion:phase gather -->
<!-- speckit-companion:phase author -->
<!-- speckit-companion:node draft-spec -->
**Where the request names two or more code areas and you have a subagent tool, dispatch one read-only worker per area first**, each returning the files that area exposes and the conventions it follows, never file contents. Those findings are what you record as `context` below and what plan reads. One area, or no subagent tool: look yourself.

2. Create `<feature_directory>/<short-name>.spec.md` with these sections, in order. Write for a business stakeholder: plain language, focused on **what** users need and **why**, not **how** to build it. Reserve `inline code` for literal identifiers a reader would copy (real names, routes, keys); never backtick ordinary nouns.

   - **User Scenarios & Testing** *(mandatory)*: the heart of the spec. Capture the feature as **prioritized user stories**, each an independently testable slice that delivers value on its own:
     - `### User Story N - <short title> (Priority: P1|P2|P3)` followed by one plain-language paragraph describing the journey.
     - **Why this priority**: one line on its value and ordering.
     - **Independent Test**: how this story alone can be exercised and what value it proves.
     - **Acceptance Scenarios**: a numbered list of `**Given** … **When** … **Then** …` cases.
     Order P1 first (the MVP slice); add as many stories as the feature genuinely needs.
   - **Edge Cases**: the boundary and error questions the implementation must answer (empty input, an entity removed while in use, duplicates, reload/persistence).
   - **Requirements › Functional Requirements** *(mandatory)*: a numbered `FR-001…` list; each a single, testable MUST/SHOULD statement. Mark a genuinely unresolvable choice `[NEEDS CLARIFICATION: …]` (max 3; prefer an informed default and record it under Assumptions instead).
   - **Key Entities** *(include when the feature involves data)*: each entity, what it represents, its key attributes and relationships. No implementation detail.
   - **Success Criteria › Measurable Outcomes** *(mandatory)*: measurable, technology-agnostic `SC-001…` outcomes (time, count, percentage, pass/fail). No framework, API, or database names.
   - **Assumptions**: the informed defaults you chose for anything the description left open.
   - **Verbatim Constraints** *(include only when the request pins exact, must-match values)*: when the user's description gives a **literal identifier or string that the result must match exactly** (a `data-testid`, a route path, an API endpoint/method, a CLI flag, an env var name, a config key, exact UI copy, a column name) record it here **verbatim, in backticks, exactly as written**. These are requirements the user pinned, so they are the one place exact identifiers belong in the spec. Do **not** paraphrase, normalize casing, pluralize, or invent a nicer name; downstream steps and the implementation MUST use these exact strings. If the request pins none, omit this section.

**Log the requirements as they're born.** The moment the Functional Requirements are written, record them all into the spec's context in one call, each with its one-line text as the title (tasks and implement fill in coverage later; best-effort, skip silently if `python3` is unavailable):
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step specify --batch '{
  "coverage": [
    {"req": "FR-001", "title": "<the requirement's one-line text>"},
    {"req": "FR-002", "title": "<…>"}
  ]
}'
```

**One call, not one per item.** `--batch` takes the whole volley as a single JSON object and applies each writer additively, so the context file is rewritten once. Emit one `--batch`.

3. Keep it business-readable. Every vague requirement should fail a "testable and unambiguous" check, so tighten it. Remove a section that genuinely does not apply rather than leaving it as "N/A". The one exception to "no implementation detail" is **Verbatim Constraints**: an exact value the *user* specified is a requirement, and dropping it is a defect.
<!-- /speckit-companion:node draft-spec -->
<!-- speckit-companion:node quality-checklist -->
4. **Spec quality checklist.** Write `<feature_directory>/checklists/requirements.md` using the template below, then run a **single** self-check pass: grade each item pass/fail, fix obvious fails in `<short-name>.spec.md` in place, and leave any genuine ambiguity as a `[NEEDS CLARIFICATION: …]` marker (max 3) for the `clarify` step. Do **not** run a multi-iteration rewrite loop or prompt the user with option tables; Companion defers interactive clarification to `clarify`. Update the checklist to reflect the final pass/fail state.

   ```markdown
   # Specification Quality Checklist: [FEATURE NAME]

   **Purpose**: Validate Companion specification completeness before planning
   **Created**: [DATE]
   **Feature**: [Link to the feature spec]

   ## Content Quality

   - [ ] No implementation details (languages, frameworks, APIs)
   - [ ] Focused on user value and business needs
   - [ ] Written for non-technical stakeholders
   - [ ] All mandatory sections completed (User Scenarios, Requirements, Success Criteria)

   ## Requirement Completeness

   - [ ] Any [NEEDS CLARIFICATION] markers are genuine ambiguities (≤3) deferred to clarify — not unresolved guesses
   - [ ] Each Functional Requirement is a single, testable MUST/SHOULD statement
   - [ ] Success criteria are measurable
   - [ ] Success criteria are technology-agnostic (no implementation details)
   - [ ] All acceptance scenarios are defined
   - [ ] Edge cases are identified
   - [ ] Scope is clearly bounded
   - [ ] Dependencies and assumptions identified

   ## Feature Readiness

   - [ ] All functional requirements have clear acceptance criteria
   - [ ] User scenarios cover primary flows
   - [ ] Feature meets measurable outcomes defined in Success Criteria
   - [ ] No implementation details leak into the specification

   ## Notes

   - Items marked incomplete require spec updates before clarify or plan
   ```

<!-- /speckit-companion:node quality-checklist -->
<!-- /speckit-companion:phase author -->
<!-- speckit-companion:phase classify -->
<!-- speckit-companion:node classify-size -->
5. **Classify the change, to right-size the ceremony.** After the spec content is drafted, decide whether this change is small enough to fast-track straight to implement, or large enough to keep the full specify → plan → tasks → implement pipeline. Apply the shared size definition below. This is a best-effort heuristic and **MUST err toward `normal`** on weak or conflicting signals.

<!-- speckit-companion:part sizing -->
- **small**: the change plausibly touches **≤ 5 files** and decomposes into **≤ 10 tasks**.
- **oversized**: the change clearly exceeds the small bar by a wide margin (broad multi-subsystem
  work, many new files, or a long task list).
- **normal**: anything in between (the default).
<!-- /speckit-companion:part sizing -->

   Estimate `projectedFiles` and `projectedTasks` for the drafted requirements, and read a `scopeSignal` from the wording (`"larger"` for rewrite | overhaul | new system | migration | redesign | …; `"smaller"` for one-line | rename | typo | tweak | copy change | …; else `"none"`). Then map the size definition above to a verdict:

   ```
   crossedGuardrail = the change exceeds the **small** bar above (more files or tasks than it allows)

   verdict = "simple"    if  the change is **small** by the definition above
                         and scopeSignal != "larger"
             "oversized" if  the change exceeds the small bar by a wide margin —
                             roughly double it (more than 10 files or more than 20
                             tasks), or spans multiple subsystems
             else "normal"
   ```

   - **Guardrail warning.** When `crossedGuardrail == true` OR `scopeSignal == "larger"`, print this line verbatim, then run the **normal** branch (never a silent fast-track):

     ```
     [companion] Change exceeds the small-change guardrail (5 files / 10 tasks) — running the full pipeline as <normal|oversized>.
     ```

     Exactly at the threshold (`projectedFiles == 5` / `projectedTasks == 10`) is the simple ceiling: it does **not** warn and stays eligible for `simple`.
<!-- /speckit-companion:node classify-size -->
<!-- speckit-companion:node persist-size -->
6. **Persist the size verdict** so `plan` and `tasks` can right-size their output without re-deciding it. Right after classifying, record the verdict on the spec's context from the repository root:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --set size=<simple|normal|oversized>
   ```
   Write `simple` when the change is the small, fast-trackable size; `oversized` when it crossed the guardrail; otherwise `normal`. This writes a plain `size` field and never touches the lifecycle log. Best-effort: if `python3` is unavailable, skip without failing the command.

   Also record the classification's **inputs**, so a later resume can judge whether the call was borderline or clear-cut (same best-effort rule):
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --classification '{"projectedFiles": <n>, "projectedTasks": <n>, "scopeSignal": "<larger|smaller|none>", "verdict": "<simple|normal|oversized>"}'
   ```
<!-- /speckit-companion:node persist-size -->
<!-- /speckit-companion:phase classify -->
<!-- speckit-companion:phase wrap-up -->
<!-- speckit-companion:node branch -->
7. **Branch on the verdict.**

   - **`simple`, minimal mode.** Write **three lean files** in this one pass so the file-driven views (top stepper, sidebar, implement progress) reconcile with the history-driven fold. Never a single combined spec file:
     - Append an **Approach** section to the already-written `<short-name>.spec.md`: the files to touch and any dependencies, in a few bullets. This is the plan content, inline, and stays the plan source-of-truth.
     - Write `<feature_directory>/plan.md` as a **short pointer** to the spec's Approach (say, a one-line blockquote linking `./<short-name>.spec.md#approach` and `./tasks.md`). Do **not** duplicate the approach bullets; `plan.md` references them.
     - Write `<feature_directory>/tasks.md` carrying the **real task checklist**: a dependency-ordered list, one per line as `- [ ] **T001** [P?] <description> + <path>` (`[P]` marks tasks that can run in parallel). This MUST be the actual checklist, not a pointer: implement progress counts these checkboxes, so a pointer would read 0/0.

     Put the task checklist **only** in `tasks.md`. A second copy in the spec would drift. `<short-name>.spec.md` keeps the Approach; `tasks.md` owns the tasks.

     Still write `<feature_directory>/checklists/requirements.md` as in step 4. Do **not** run `speckit.companion.plan` or `speckit.companion.tasks`: the three lean files plus the lifecycle fold below record those steps as satisfied.
   - **`normal`, full pipeline.** Write `<short-name>.spec.md` only: no appended Approach section, no `plan.md` / `tasks.md` here, no lifecycle fold. Plan and tasks are produced and recorded by their own `speckit.companion.plan` and `speckit.companion.tasks` runs.
<!-- /speckit-companion:node branch -->
<!-- speckit-companion:node finalize -->
**Output**: `<feature_directory>/<short-name>.spec.md` + `<feature_directory>/checklists/requirements.md`. In **simple** mode the spec additionally carries an **Approach** section, and two lean files sit alongside it: `plan.md` (a pointer to that Approach) and `tasks.md` (the real `- [ ] **T001** …` checklist; the task list lives here, not in the spec). In **normal** mode the spec holds the four sections only, and no `plan.md` / `tasks.md` are written here.

**Capture the whole wrap-up in one call.** Everything this step learned goes in a single `--batch`: what it worked *from* (the living specs loaded above, the areas investigated, the constraints honored), the distilled intent, the explicit non-goals, and the workflow identity.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --batch '{
  "context": ["living spec: <name>", "area: <path or subsystem>", "constraint: <rule honored>"],
  "expectations": ["<out-of-scope item>", "<another>"],
  "set": {"intent": "<one-line goal>", "workflow": "companion"}
}'
```

Best-effort as a whole: skip silently if `python3` is unavailable. Omit `context` when there is nothing worth recording and `expectations` when the spec declares no non-goals; never invent either. **`workflow` is the one field that is not optional**: without it the shared writer defaults to `speckit`, and a later footer advance dispatches the stock command.

**On a `simple` run, add the approach to the same call.** A `simple` run writes its plan inline and never reaches `plan`, where a full run records it. So when `verdict == "simple"`, put `"approach": "<one-line summary of the Approach section>"` in the `set` map alongside the rest rather than paying a second call.

**Record completion.** After `<short-name>.spec.md` is written, close the specify step. The extension stamps the real end, so do **not** hand-write an `ai` complete for specify:
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step specify --status specified --kind complete --by extension
```

**Fast-path living-spec load (simple mode only; best-effort, opt-in, read-only).** A `simple` run never reaches `plan`, where a full run loads living specs again with the touched files known. So if the pre-draft load recorded nothing, do it **now**: the touched files are known post-draft. Read `<feature_directory>/.spec-context.json`. If `livingSpecs.loaded` is already populated, skip this; never re-resolve or duplicate. Otherwise run the deterministic recorder against the files this change touches:
```bash
python3 .specify/extensions/companion/scripts/record-living-specs.py --feature-dir <feature_directory> --changed <files this change touches…>
```
You may also read the matched specs into context (best-effort), but the recorder is the reliable write. Same contract as the load step: a missing config, resolver, or spec file is a silent no-op that never blocks the fold.

**Fast-path lifecycle fold (simple mode only).** When `verdict == "simple"`, record the folded `plan` and `tasks` steps so the history-driven panels read them as satisfied-by-fast-path and the spec lands ready for implement. These are real lifecycle boundaries, stamped like every other trusted step (**`--by extension`, step-level, no substep**). Run them **in order, after** the specify completion above. Do not hand-write them, and do not run them for a `normal` verdict:
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step plan  --kind start    --by extension
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step plan  --kind complete --by extension
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step tasks --kind start    --by extension
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step tasks --kind complete --status ready-to-implement --by extension
```
After the fold, the spec sits at the **tasks** step with `status: ready-to-implement`; the developer triggers implement next. Do **not** write a `completed` status: the final completed gate stays a user action.
<!-- /speckit-companion:node finalize -->
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

**The next step is `plan`**: dispatch `speckit.companion.plan <feature_dir>`. Unless this spec was classified `simple`, in which case plan and tasks are already folded and the next step is `implement`: `speckit.companion.implement <feature_dir>`.

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
