---
description: "Companion specify — spec.md with prioritized user stories"
---

## User Input

```text
$ARGUMENTS
```

<!-- speckit-companion:part speckit-hooks -->
## Pre-Execution Checks — stock spec-kit extension hooks

Companion runs **on top of** stock spec-kit, so a project's installed spec-kit **extensions** (git, and any others registered in `.specify/extensions.yml`) must still fire on a Companion run exactly as they do on a stock `/speckit.*` run. This is separate from Companion's own node-hooks (`.specify/companion.yml`): both fire. Like the rest of the pipeline, checking these hooks must **never fail the host command** — if anything is missing or malformed, skip silently and continue.

Let `<step>` be this command's phase: `specify`, `plan`, `tasks`, or `implement`.

**Before-hooks — run these *now*, before any of the work below.**
- Check whether `.specify/extensions.yml` exists in the project root. If it does not, skip silently — there are no hooks.
- If it exists, read it and look for entries under `hooks.before_<step>`. If the YAML cannot be parsed, skip hook checking silently and continue normally.
- Filter out hooks where `enabled` is explicitly `false`. A hook with no `enabled` field is enabled by default.
- Do **not** interpret or evaluate a hook's `condition` expression yourself: a hook with no `condition` (or a null/empty one) is executable; a hook with a non-empty `condition` is left to the HookExecutor — skip it here.
- For each executable hook, emit one block based on its `optional` flag:
  - **Optional** (`optional: true`):
    ```
    ## Extension Hooks

    **Optional Pre-Hook**: {extension}
    Command: `/{command}`
    Description: {description}

    Prompt: {prompt}
    To execute: `/{command}`
    ```
  - **Mandatory** (`optional: false`):
    ```
    ## Extension Hooks

    **Automatic Pre-Hook**: {extension}
    Executing: `/{command}`
    EXECUTE_COMMAND: {command}

    Wait for the result of the hook command before proceeding to the Outline.
    ```
- If no before-hooks are registered, skip silently.

**After-hooks — run these once this command's work is fully reported, before handing off.**
- Re-check `.specify/extensions.yml`; if absent or unparseable, skip silently. Look under `hooks.after_<step>`, applying the same `enabled` / `condition` filtering as above.
- For each executable hook, emit one block:
  - **Optional** (`optional: true`):
    ```
    ## Extension Hooks

    **Optional Hook**: {extension}
    Command: `/{command}`
    Description: {description}

    Prompt: {prompt}
    To execute: `/{command}`
    ```
  - **Mandatory** (`optional: false`):
    ```
    ## Extension Hooks

    **Automatic Hook**: {extension}
    Executing: `/{command}`
    EXECUTE_COMMAND: {command}
    ```
- If no after-hooks are registered, skip silently.

For `specify`, branch creation is normally one of these `before_specify` hooks (the git extension); spec directory and file creation are always handled by the command body itself.
<!-- /speckit-companion:part speckit-hooks -->

## Outline

Produce a feature specification: prioritized user stories with acceptance scenarios, functional requirements, key entities, edge cases, and measurable success criteria, then a quality checklist.
1. **Resolve the feature directory — mint a fresh dir for new work.** `.specify/feature.json` is an **output** of this step, not an input to reuse: it points at the *previous* spec (frequently already completed), so reusing it would clobber finished work. Pick the target:
   - If the request explicitly names a target path (or `SPECIFY_FEATURE_DIRECTORY` is set), use it.
   - Otherwise create the next numbered dir: scan `specs/` for the highest `NNN-…` prefix, derive a 2–4 word short-name from the description, and use `specs/<NNN+1>-<short-name>/`. **Never write into a directory that already contains a `spec.md`** — that's a stale pointer to a prior spec, not this feature.
   Create `<feature_directory>/`, point `.specify/feature.json` at it, then record the **specify START** so the step's duration begins now (the script stamps the real clock — do not hand-write this):
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step specify --status specifying --kind start --by extension
   ```

**Load living specs — arrive pre-briefed (best-effort, opt-in, read-only).** Before drafting, check whether this project keeps **living specs** for the areas this change touches, and if so fold them into your context so you are not re-learning the codebase from scratch. This whole step is **opt-in by presence** and must **never** fail or slow the command — on any miss (no config, feature off, no resolver, no spec file) skip silently and draft as usual. It is strictly **read-only**: never create or edit a `capabilities/<name>/spec.md` from here.

   - **Record deterministically first — never hand-judge the gate.** Don't decide "is this project configured?" or "which capabilities apply?" yourself; that judgment is exactly what silently skipped the load on real runs. Run the deterministic recorder with the files this change will touch (the surface you've identified for the feature; if none are known yet, skip the load). It re-reads the registry (`living-specs.yml`, or a legacy `livingSpecs` block in `.specify/companion.yml`), gates on `enabled`, runs the resolver, writes the matched capabilities (leaf-first) onto `livingSpecs.loaded`, **and writes the one-line `last_action` audit breadcrumb itself** — so "correctly did nothing" and "capture broke" stay distinguishable without any AI prose:
     ```bash
     python3 .specify/extensions/companion/scripts/record-living-specs.py --feature-dir <feature_directory> --changed <in-scope files…>
     ```
     This writes only additive `livingSpecs.loaded` + the breadcrumb on `.spec-context.json`; it never touches the lifecycle log. It is a silent no-op that exits 0 when the feature is off, nothing matches, or the registry/resolver can't be read — so it never fails or slows the command; and, exactly like every other capture call here, skip it silently if `python3` or the script is unavailable. This call is the reliable record the later `plan` step and the Overview chips read.
   - **Then read what it recorded, leaf first.** Read `livingSpecs.loaded` back from `<feature_directory>/.spec-context.json`. If it is empty, there is nothing to load — continue to the spec draft. Otherwise, for each recorded capability (in the recorded order — most-specific first), resolve its spec path and read it into your working context:
     ```bash
     python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --changed <in-scope files…> --json
     ```
     Read each match's `spec` path (centralized capabilities resolve to `capabilities/<name>/spec.md`; colocated ones carry their own path): the leaf capability is the **primary** frame for this change, a parent capability is the surrounding **context**. Skip any the resolver marked `"exists": false` (or missing on disk); load the rest. These living specs are background you must honor while drafting — they describe how the area already behaves. This reading is best-effort context; the recorder above is the reliable write.

2. Create `<feature_directory>/spec.md` with these sections, in order. Write for a business stakeholder — plain language first, focused on **what** users need and **why**, not **how** to build it. Reserve `inline code` for literal identifiers a reader would copy (real names, routes, keys); never backtick ordinary nouns.

   - **User Scenarios & Testing** *(mandatory)* — the heart of the spec. Capture the feature as **prioritized user stories**, each an independently testable slice that delivers value on its own:
     - `### User Story N - <short title> (Priority: P1|P2|P3)` followed by one plain-language paragraph describing the journey.
     - **Why this priority** — one line on its value and ordering.
     - **Independent Test** — how this story alone can be exercised and what value it proves.
     - **Acceptance Scenarios** — a numbered list of `**Given** … **When** … **Then** …` cases.
     Order P1 first (the MVP slice); add as many stories as the feature genuinely needs.
   - **Edge Cases** — a short list of the boundary and error questions the implementation must answer (empty input, an entity removed while in use, duplicates, reload/persistence).
   - **Requirements › Functional Requirements** *(mandatory)* — a numbered `FR-001…` list; each a single, testable MUST/SHOULD statement. Mark a genuinely unresolvable choice `[NEEDS CLARIFICATION: …]` (max 3; prefer an informed default and record it under Assumptions instead).
   - **Key Entities** *(include when the feature involves data)* — each entity: what it represents, its key attributes and relationships, no implementation detail.
   - **Success Criteria › Measurable Outcomes** *(mandatory)* — measurable, technology-agnostic `SC-001…` outcomes (time, count, percentage, pass/fail). No framework, API, or database names.
   - **Assumptions** — the informed defaults you chose for anything the description left open.
   - **Verbatim Constraints** *(include only when the request pins exact, must-match values)* — when the user's description gives a **literal identifier or string that the result must match exactly** — a `data-testid`, a route path, an API endpoint/method, a CLI flag, an env var name, a config key, exact UI copy, a column name — record it here **verbatim, in backticks, exactly as written**. These are *requirements the user pinned*, not implementation details you may rephrase, so they are the one place exact identifiers belong in the spec. Do **not** paraphrase, normalize casing, pluralize, or invent a "nicer" name; downstream steps and the implementation MUST use these exact strings. If the request pins none, omit this section.

**Log the requirements as they're born.** The moment the Functional Requirements are written, record each one into the spec's context — one call per requirement, its one-line text as the title — so requirements exist as readable, queryable entries from the first step (tasks and implement fill in coverage later; best-effort, skip silently if `python3` is unavailable):
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --coverage-req FR-001 --title "<the requirement's one-line text>"
```

3. Keep it business-readable. Every vague requirement should fail a "testable and unambiguous" check — tighten it. Remove a section that genuinely does not apply rather than leaving it as "N/A". The one exception to "no implementation detail" is **Verbatim Constraints**: an exact value the *user* specified is a requirement, and dropping it (forcing a later step to guess) is a defect.
4. **Spec quality checklist.** Write `<feature_directory>/checklists/requirements.md` using the template below, then run a **single** self-check pass: grade each item pass/fail, fix obvious fails in `spec.md` in place, and leave any genuine ambiguity as a `[NEEDS CLARIFICATION: …]` marker (max 3) for the `clarify` step. Do **not** run a multi-iteration rewrite loop or prompt the user with option tables — Companion defers interactive clarification to `clarify`. Update the checklist to reflect the final pass/fail state.

   ```markdown
   # Specification Quality Checklist: [FEATURE NAME]

   **Purpose**: Validate Companion specification completeness before planning
   **Created**: [DATE]
   **Feature**: [Link to spec.md]

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

5. **Classify the change — right-size the ceremony.** After the spec content is drafted, decide whether this change is small enough to fast-track straight to implement, or large enough to keep the full specify → plan → tasks → implement pipeline. Apply the shared size definition below — the same one the standalone size step uses, so the small/large bar is authored in exactly one place. This is a best-effort heuristic and **MUST err toward `normal`** on weak or conflicting signals — a change is never under-planned by accident.

<!-- speckit-companion:part sizing -->
- **small** — the change plausibly touches **≤ 5 files** and decomposes into **≤ 10 tasks**.
- **oversized** — the change clearly exceeds the small bar by a wide margin (broad multi-subsystem
  work, many new files, or a long task list).
- **normal** — anything in between (the default).

The two constants (5 files / 10 tasks) are the same guardrail the old `complexityFastPath` used.
<!-- /speckit-companion:part sizing -->

   Estimate `projectedFiles` and `projectedTasks` for the drafted requirements, and read a `scopeSignal` from the wording (`"larger"` for rewrite | overhaul | new system | migration | redesign | …; `"smaller"` for one-line | rename | typo | tweak | copy change | …; else `"none"`). Then map the size definition above to a verdict:

   ```
   crossedGuardrail = the change exceeds the **small** bar above (more files or tasks than it allows)

   verdict = "simple" if  the change is **small** by the definition above
                      and scopeSignal != "larger"
             else "normal"
   ```

   - **Guardrail warning.** When `crossedGuardrail == true` OR `scopeSignal == "larger"`, print this line verbatim, then run the **normal** branch (never a silent fast-track):

     ```
     [companion] Change exceeds the small-change guardrail (5 files / 10 tasks) — running the full pipeline.
     ```

     Exactly-at-threshold (`projectedFiles == 5` / `projectedTasks == 10`) is the simple ceiling — it does **not** warn and stays eligible for `simple`.

6. **Persist the size verdict** so the later steps (`plan`, `tasks`) can right-size their output without re-deciding it. Right after classifying, record the verdict on the spec's context from the repository root:
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --set size=<simple|normal|oversized>
   ```
   Write `simple` when the change is the small, fast-trackable size; `oversized` when it crossed the guardrail; otherwise `normal`. This only writes a plain `size` field — it never touches the lifecycle log. Best-effort: if `python3` is unavailable, skip without failing the command.

   Also record the classification's **inputs**, not just its verdict, so a later resume can judge whether the call was borderline or clear-cut (same best-effort rule):
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --classification '{"projectedFiles": <n>, "projectedTasks": <n>, "scopeSignal": "<larger|smaller|none>", "verdict": "<simple|normal|oversized>"}'
   ```
6. **Branch on the verdict.**

   - **`simple` — minimal mode.** Write **three lean files** in this one pass so the file-driven views (top stepper, sidebar, implement progress) reconcile with the history-driven fold — never a single combined `spec.md`:
     - Append an **Approach** section to the already-written `spec.md` — the files to touch and any dependencies, in a few bullets (the plan content, inline; this stays the plan source-of-truth).
     - Write `<feature_directory>/plan.md` as a **short pointer** to the spec's Approach (e.g. a one-line blockquote linking `./spec.md#approach` and `./tasks.md`). Do **not** duplicate the approach bullets — `plan.md` references them.
     - Write `<feature_directory>/tasks.md` carrying the **real task checklist** — a dependency-ordered list, one per line as `- [ ] **T001** [P?] <description> + <path>` (`[P]` marks tasks that can run in parallel). This MUST be the actual checklist, not a pointer: implement progress counts these checkboxes, so a pointer would read 0/0.

     Put the task checklist **only** in `tasks.md` — do **not** keep a second copy in `spec.md` (the duplicate would drift). `spec.md` keeps the Approach; `tasks.md` owns the tasks.

     Still write `<feature_directory>/checklists/requirements.md` as in step 4. Do **not** run `/speckit.companion.plan` or `/speckit.companion.tasks` — the three lean files plus the lifecycle fold below record those steps as satisfied.
   - **`normal` — full pipeline.** Write `spec.md` only (no appended Approach section, no `plan.md` / `tasks.md` here, no lifecycle fold). The existing pipeline continues unchanged: plan and tasks are produced and recorded by their own `/speckit.companion.plan` and `/speckit.companion.tasks` runs.

**Output**: `<feature_directory>/spec.md` + `<feature_directory>/checklists/requirements.md`. In **simple** mode, `spec.md` additionally carries an **Approach** section, and two lean files are emitted alongside it — `plan.md` (a pointer to that Approach) and `tasks.md` (the real `- [ ] **T001** …` checklist; the task list lives here, not in `spec.md`); in **normal** mode, `spec.md` holds the four sections only and no `plan.md` / `tasks.md` are written here.

**Capture the context (the C of Intent/Context/Expectations).** Record what this run worked *from* — the living specs loaded above (when any), the key files/areas you investigated, and the constraints you honored — one short entry each (best-effort; skip silently if `python3` is unavailable; omit entirely when there is nothing worth recording):
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --context "living spec: <name>" --context "area: <path or subsystem>" --context "constraint: <rule honored>"
```

**Capture the goal and the fence.** Before closing the step, persist the spec's distilled intent (one sentence — what this feature is *for*) and each explicit non-goal / out-of-scope item, so a resume or a colliding future spec can read them without re-reading the spec (best-effort; skip silently if `python3` is unavailable):
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --set intent="<one-line goal>"
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --expectation "<out-of-scope item>" --expectation "<another>"
```
Omit the `--expectation` call when the spec declares no non-goals — never invent them.

**Capture the approach (simple mode only).** A `simple` run writes the plan inline as the `## Approach` section of `spec.md` and never reaches `plan` (which is where a full run records `--set approach`). So when `verdict == "simple"`, persist that same one-line approach onto `.spec-context.json` so the viewer Overview's APPROACH card reads it (best-effort; skip silently if `python3` is unavailable):
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --set approach="<one-line summary of the Approach section>"
```

**Pin the workflow identity.** Record that this spec runs the **Companion** workflow, so the viewer advances it on Companion — not stock — at `plan`, `tasks`, and `implement`. Without this the shared writer defaults `workflow` to `speckit`, and a later footer advance dispatches the stock command. This is a **required deterministic write** (only skip if `python3` is genuinely unavailable), not best-effort:
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --set workflow=companion
```

**Record completion.** After `spec.md` is written, close the specify step — the extension stamps the real end (do **not** hand-write an `ai` complete for specify):
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step specify --status specified --kind complete --by extension
```

**Fast-path living-spec load (simple mode only — best-effort, opt-in, read-only).** A `simple` run never reaches `plan`, which is where a full run loads living specs a second time with the touched files known. So if the pre-draft load recorded nothing (the surface wasn't known yet), do that load **now** — the touched files are known post-draft. Read `<feature_directory>/.spec-context.json`: if `livingSpecs.loaded` is already populated, skip this (never re-resolve or duplicate). Otherwise record what applies with the deterministic recorder against the files this change touches — it gates on `enabled`, runs the resolver, and writes the matched capabilities (leaf-first) onto `livingSpecs.loaded` itself, so the fast path can't lose the record to a misjudged "not configured":
```bash
python3 .specify/extensions/companion/scripts/record-living-specs.py --feature-dir <feature_directory> --changed <files this change touches…>
```
You may still read the matched specs into context for drafting (best-effort), but the recorder is the reliable write. Same contract as the load step: any missing config, resolver, or spec file is a silent no-op that never blocks the fold.

**Fast-path lifecycle fold (simple mode only).** When `verdict == "simple"`, record the folded `plan` and `tasks` steps so the history-driven panels read them as satisfied-by-fast-path — pairing with the lean `plan.md` / `tasks.md` files above, which make the file-driven stepper, sidebar, and implement progress agree — and the spec lands ready for implement. These are the step's real lifecycle boundaries, stamped by the extension like every other trusted step (**`--by extension`, step-level, no substep**), so the timing display counts specify, plan, and tasks as measured phases. Run them **in order, after** the specify completion above (each call stamps its own real clock — do not hand-write these, and do not run them for a `normal` verdict):
```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step plan  --kind start    --by extension
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step plan  --kind complete --by extension
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step tasks --kind start    --by extension
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step tasks --kind complete --status ready-to-implement --by extension
```
After the fold, the spec sits at the **tasks** step with `status: ready-to-implement`; the developer triggers implement next. Do **not** write a `completed` status — the final completed gate stays a user action.


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

<!-- speckit-companion:part self-advance -->
## Self-advance — hand off to the next step

This is one step in the Companion pipeline. How the run continues depends on the environment you are running in; do not invoke a separate headless/deterministic run command for the everyday flow.

- **On an agentic CLI that keeps acting after a step finishes:** once this step's work is complete, read the Companion workflow definition (`speckit-extension/workflows/speckit-companion.workflow.yml`) to learn which step comes next, then continue into it on your own — dispatch the next step's `/speckit.companion.*` command and keep going through the pipeline.
- **Pause at every review gate.** Where the workflow marks a `gate` (e.g. review-spec, review-plan), stop and wait for approval rather than running past it. Only continue once the gate is approved.
- **Terminal step after implement.** After the implementation step finishes (and any commit step), the workflow's final step is `mark-complete`. Run it so the spec lands at `status: completed`. That step writes `completed` only through `write-context.py --mark-complete`, which refuses unless the spec is already `implemented` — never introduce a second completed-writer.
- **Degrade gracefully on a one-shot environment.** If your environment runs one step and then stops, the handoff simply does not fire: finish this step, record its progress, and stop. The run stays valid and resumable, and the next step is triggered manually (by the developer or the companion panel). Completion likewise stays a manual action there.
<!-- /speckit-companion:part self-advance -->

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
