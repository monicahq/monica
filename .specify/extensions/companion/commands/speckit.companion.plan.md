---
description: "Companion plan — implementation plan with research & design artifacts"
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

Produce an implementation plan and its design artifacts in phases: load context → write `plan.md` (Summary, Constitution Check, Project Structure) → Phase 0 research → Phase 1 design (data model, contracts).
**Right-size this plan to the change.** Before anything else, read the recorded size from the spec's context — `.spec-context.json` → the `size` field (treat a missing value as `normal`). That size sets the budget for the steps below; **apply it to them, omitting anything it says to skip.**

- **`normal` or `oversized`** — produce the full plan and every design artifact exactly as the steps describe. No trimming.
- **`simple`** — a small change does not need the full ceremony. Produce a **lean** plan:
  - `plan.md`: keep the **Summary** only. **Skip the Project Structure section** (the task list already names every file) and **skip the Constitution Check** unless there is a real violation to flag.
  - **Skip `data-model.md`** — fold the one or two types into the plan's prose.
  - Write the design rationale as a short **Key Decisions** note folded into `plan.md` (a few Decision/why lines), not a separate `research.md`, unless a decision genuinely needs its own page.
  - Generate `contracts/` only if the feature exposes an interface a consumer or test codes against.

This budget governs every step that follows. Where a later step would produce something the budget skips, omit it — do not produce it and then delete it.
1. Read `.specify/feature.json` for the feature directory, then record the **plan START** so the step's duration begins now (the script stamps the real clock; do not hand-write plan timing):
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step plan --status planning --kind start --by extension
   ```
   Load `<feature_directory>/spec.md` and `.specify/memory/constitution.md` if present — the inputs the plan must satisfy. Then **investigate the codebase** to understand where this feature attaches: the patterns it must follow (state/store, routing, persistence, component and test conventions) and the exact files it will touch. Read inline by default. **The exception worth parallelizing:** a *large or unfamiliar* codebase with several **independent areas** to map — there, reading is genuinely heavy (each area means opening many files), so when your host has subagents, dispatch one read-only subagent per area in a single message, each returning a **distilled finding** (the pattern to copy, the concrete file paths, the conventions to match) rather than a dump of file contents. That is the case where a separate worker pays for its startup. For a small or familiar codebase, just read the areas yourself in turn — identical result, less overhead. Collect the findings as the research basis for the plan.

   **Reuse the living specs already loaded (best-effort, opt-in, read-only).** If `specify` loaded living specs for this feature, it recorded them on `.spec-context.json` under `livingSpecs.loaded` — **reuse that record instead of re-resolving**. Read `<feature_directory>/.spec-context.json`; if `livingSpecs.loaded` lists capability names, read each one's spec **in the listed order (most-specific first)** so the plan honors how those areas already behave — a centralized capability's spec is at `capabilities/<name>/spec.md`; if a name doesn't resolve there it is colocated, so resolve its `spec` path with `python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --all --json` (match the recorded name). Only if no list was recorded (e.g. `plan` runs without a prior `specify` load) and `livingSpecs.enabled` is true may you resolve fresh with `python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --changed <files…> --json` and read the matches. Either way this is read-only and best-effort: a missing config, missing record, or missing spec file is skipped silently and never blocks the plan — and never write a living spec from here.

   **Pull the architecture tier ONLY for an architecture-significant plan (lazy, opt-in, read-only).** A capability's living spec ships a cold sibling — `<spec>.arch.md` (structure, diagrams, the decisions behind the area's shape) — that the hot `.spec.md` deliberately leaves out. Load it **only when this plan is architecture-significant**, judged by the same recorded size signal the budget above uses: read `.spec-context.json` → `size` and load the arch tier when it is **`normal` or `oversized`**, and **never** when it is **`simple`** (a small fast-path change must not pay to pull the cold tier). Do not hardcode the `.arch.md` filename — ask the resolver for each loaded capability's tier paths and read the `arch` one when it exists: `python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --all --json` returns each capability's `tiers.arch.path` and `tiers.arch.exists`. For every capability you loaded above, if `tiers.arch.exists` is true read `tiers.arch.path` into context as the structural frame for the plan; skip any whose arch tier is absent. This is best-effort and read-only exactly like the spec load — a missing config, missing tier, or unavailable resolver is skipped silently and never blocks or fails the plan, and you never write an `.arch.md` from here.
2. Create `<feature_directory>/plan.md` with these sections, in order (this is the full, `normal`/`oversized` shape — the **size budget above governs**: at `simple` size it keeps only the Summary and skips the rest unless genuinely needed). Lead each with prose; reserve `inline code` for real identifiers (paths, types, packages), not ordinary nouns — a sentence that is mostly code spans is a rewrite.
   - **Summary** — 2–4 plain-language sentences: the primary requirement plus the technical approach. If a stack choice genuinely isn't obvious from the codebase (a new language, a newly-added dependency, a non-default storage or test setup), name it in a sentence here; otherwise don't restate the project's known stack.
   - **Project Structure** — the concrete source layout this feature touches, as a short tree of real directories/files, plus a one-line **Structure Decision**. Use the actual paths; do not leave placeholder option-trees in the output. *(Skipped at `simple` size per the budget — the task list already names every file.)*
3. **Constitution Check** — add a `## Constitution Check` section to `plan.md` as a table: one row per constitution principle with a PASS / justified-violation assessment. This is a gate before Phase 0 research, re-checked after Phase 1 design. If a violation is genuinely necessary, justify it in a short **Complexity Tracking** table (violation | why needed | simpler alternative rejected). Omit Complexity Tracking when there are no violations; ERROR on an unjustified gate failure.
4. **Phase 0 — Research (first).** Write `<feature_directory>/research.md` before the Phase 1 docs, since they build on its decisions. *(The size budget above governs: at `simple` size, fold the rationale into a short Key Decisions note in `plan.md` instead of a separate `research.md`.)* For each genuine unknown the plan leaves open — a stack or dependency choice the codebase doesn't already settle, an integration, or a significant design choice — record a short entry as **Decision** (what you chose) / **Rationale** (why) / **Alternatives considered** (what else, and why not). Resolve every `NEEDS CLARIFICATION` here — this is where a maintainer sees *why* the design is shaped this way.

5. **Phase 1 — Design & contracts.** With research settled, generate the design artifacts the size budget keeps. They are **independent documents that share no evolving state**, so write them in any order. Inline (one after another) is the default — composing a short design doc is light work that doesn't pay back a separate worker's startup. Only when the documents are genuinely large *and* your host has subagents is it worth generating them concurrently (one subagent per document); the result is identical either way.
   - `<feature_directory>/data-model.md` — the entities this feature introduces or reshapes: fields, relationships, validation rules drawn from the requirements, and any state transitions.
   - `<feature_directory>/contracts/` — the interface the feature exposes (API / CLI / schema, or a UI contract listing routes and the identifiers a consumer/test codes against). **Copy every identifier from the spec's Verbatim Constraints exactly — never rename, recase, pluralize, or invent an identifier the spec already pinned; those exact strings *are* the contract.** Skip the directory only when the feature exposes no interface at all.
   After the documents return, re-check the Constitution Check against the final design.

6. **Capture the plan's reasoning** so the *why* survives the session (best-effort; JSON when you can produce it, bare text when not; skip silently if `python3` is unavailable):
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --set approach="<2-3 sentence how-summary>"
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --decision '{"decision": "<what you chose>", "why": "<why>", "rejected": "<the alternative not taken>"}'
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step plan --step-summary '{"summary": "<one-line rollup>", "key_finding": "<the most load-bearing thing you learned>"}'
   ```
   Record one `--decision` per genuine choice from Phase 0 (repeat the flag or the call); skip trivia. These are additive and de-duped — re-running them never duplicates.

**Output**: `<feature_directory>/plan.md` plus `research.md`, `data-model.md`, and `contracts/` when applicable.
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
