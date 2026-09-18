---
description: "Companion plan — implementation plan with research & design artifacts"
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

Produce an implementation plan and its design artifacts in phases: load context → write `plan.md` (Summary, Constitution Check, Project Structure) → Phase 0 research → Phase 1 design (data model, contracts).
<!-- speckit-companion:phase gather -->
<!-- speckit-companion:node size-budget -->
**Right-size this plan to the change.** Before anything else, read the recorded size from the spec's context: `.spec-context.json` → the `size` field (a missing value means `normal`). That size sets the budget for the steps below. **Apply it to them, omitting anything it says to skip.**

- **`normal`**: produce the full plan and every design artifact exactly as the step describes. No trimming.
- **`oversized`**: produce the same full plan and every design artifact, and open it with a short **Scale note**: one or two sentences naming how many files and areas the change spans and what a reader should watch for. Size never trims here. An oversized change gets *more* signposting, not less.
- **`simple`**: produce a **lean** plan:
  - `plan.md`: keep the **Summary** only. **Skip the Project Structure section** (the task list already names every file) and **skip the Constitution Check** unless there is a real violation to flag.
  - **Skip `data-model.md`**; fold the one or two types into the plan's prose.
  - Write the design rationale as a short **Key Decisions** note folded into `plan.md` (a few Decision/why lines), not a separate `research.md`, unless a decision genuinely needs its own page.
  - Generate `contracts/` only if the feature exposes an interface a consumer or test codes against.

This budget governs every step that follows. Where a later step would produce something the budget skips, omit it. Do not produce it and then delete it.
<!-- /speckit-companion:node size-budget -->
<!-- speckit-companion:node load-living-specs -->
**Read what is already written down before sending anyone to rediscover it.** This runs ahead of the codebase investigation on purpose: a worker sent to learn an area's conventions from source will relearn what a requirement or a rules line already states, and its finding can then disagree with the spec with nothing to reconcile the two. Load first, investigate second, and let the investigation fill gaps rather than repeat the record.

**Reuse the living specs already loaded, and read them by requirement (best-effort, opt-in, read-only).** If `specify` loaded living specs for this feature, it recorded them on `.spec-context.json` under `livingSpecs.loaded`. **Reuse that record instead of re-resolving.** When the files this plan touches reach a capability the record misses, run `record-living-specs.py --feature-dir <feature_directory> --changed <every file this change touches>` once; it is additive and de-duped, so the record only widens. Read `<feature_directory>/.spec-context.json`; if `livingSpecs.loaded` lists capability names, ask the resolver what each should contribute for the files this change touches:
```bash
python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --changed <files…> --requirements-for --follow-aligns --json
```
`--follow-aligns` adds one hop: a requirement can name a rule under another capability that constrains it, and a rule reached that way arrives marked `"via": "aligns"` with `"matched": false`, because no file you are touching belongs to it. Honor it anyway. It is the case the file match cannot see: the rule lives somewhere you are not editing, which is exactly why nobody would think to read it. Each entry arrives most-specific first. `"whole": true` means read that `spec` file entirely. `"whole": false` comes with a `purpose` and the `requirements` to contribute, which is all you read. Each requirement carries its `heading` and its full `body`, so you get the normative prose and its scenarios, not headings to go and resolve. The listed requirements cover the files you are changing plus every requirement its author left unmarked, so a partly-marked spec can never starve the plan of context. If the resolver is unavailable or the call fails, read each recorded capability's spec whole: a centralized capability's spec is at `capabilities/<name>/<name>.spec.md`, and a name that doesn't resolve there is colocated, so find its `spec` path with `--all --json` and match the recorded name. Only if no list was recorded (say `plan` runs without a prior `specify` load) and `livingSpecs.enabled` is true may you resolve fresh against the files this change touches. Either way this is read-only and best-effort: a missing config, missing record, or missing spec file is skipped silently and never blocks the plan. Never write a living spec from here.

**Honor the project's authored plan rules.** That same call carries a `rules` object. `rules.plan` is a short list of one-line house rules the project wrote once in its registry. Read **only** `rules.plan` here (`rules.spec` belongs to the specify step) and treat each line as an instruction while writing the plan and its design artifacts. An empty list is the normal case: say nothing about rules and plan as usual. These lines shape *how* the plan is written. They never add requirements or override anything in this command body.
<!-- /speckit-companion:node load-living-specs -->
<!-- speckit-companion:node gather-context -->
1. Read `.specify/feature.json` for the feature directory. The step's start is already stamped, above. Load the feature spec (`<feature_directory>/<short-name>.spec.md`, or `spec.md` in a project written before this) and `.specify/memory/constitution.md` if present. Those are the inputs the plan must satisfy. **Read what `specify` recorded before you open a file.** `specify` writes what it read onto `.spec-context.json` under `context`: the code areas it looked at and the constraints it found there. Read those entries first. They name where the feature attaches, so open the files they point at instead of rediscovering them. They carry locations, not content, so you still open the files that matter, filling gaps in a map you were handed. If no `context` entries were recorded, investigate from scratch as below.

Then **investigate the codebase** to understand where this feature attaches: the patterns it must follow (state/store, routing, persistence, component and test conventions) and the exact files it will touch. Read inline by default. **When the recorded `context` names two or more `area:` entries and you have a subagent tool, dispatch one read-only worker per area in a single message.** That is an instruction, not a judgement about size. Hand each worker the slice the load above already resolved for its area, so it reads code to fill the gaps rather than to relearn what is written down. Each worker returns a **distilled finding**: the pattern to copy, the concrete file paths, the conventions to match, and anything the code does that its slice does not account for. Never file contents. One area, or no subagent tool: read it yourself. Collect the findings as the research basis for the plan.
<!-- /speckit-companion:node gather-context -->
<!-- /speckit-companion:phase gather -->
<!-- speckit-companion:phase author -->
<!-- speckit-companion:node plan-doc -->
2. Create `<feature_directory>/plan.md` with these sections, in order. This is the full `normal`/`oversized` shape and the **size budget above governs**: at `simple` size it keeps only the Summary and skips the rest unless genuinely needed. Lead each section with prose. Reserve `inline code` for real identifiers (paths, types, packages), not ordinary nouns: a sentence that is mostly code spans is a rewrite.
   - **Summary**: 2–4 plain-language sentences giving the primary requirement plus the technical approach. If a stack choice genuinely isn't obvious from the codebase (a new language, a newly-added dependency, a non-default storage or test setup), name it in a sentence here. Otherwise don't restate the project's known stack.
   - **Project Structure**: the concrete source layout this feature touches, as a short tree of real directories/files, plus a one-line **Structure Decision**. Use the actual paths; do not leave placeholder option-trees in the output. *(Skipped at `simple` size per the budget.)*
<!-- /speckit-companion:node plan-doc -->
<!-- /speckit-companion:phase author -->
<!-- speckit-companion:phase check -->
<!-- speckit-companion:node constitution-check -->
3. **Constitution Check**: add a `## Constitution Check` section to `plan.md` as a table: one row per constitution principle with a PASS / justified-violation assessment. This is a gate before Phase 0 research, re-checked after Phase 1 design. If a violation is genuinely necessary, justify it in a short **Complexity Tracking** table (violation | why needed | simpler alternative rejected). Omit Complexity Tracking when there are no violations; ERROR on an unjustified gate failure.
<!-- /speckit-companion:node constitution-check -->
<!-- /speckit-companion:phase check -->
<!-- speckit-companion:phase wrap-up -->
<!-- speckit-companion:node side-files -->
4. **Phase 0, research first.** Write `<feature_directory>/research.md` before the Phase 1 docs, which build on its decisions. *(The size budget above governs: at `simple` size, fold the rationale into a short Key Decisions note in `plan.md` instead of a separate `research.md`.)* For each genuine unknown the plan leaves open (a stack or dependency choice the codebase doesn't already settle, an integration, or a significant design choice) record a short entry as **Decision** (what you chose) / **Rationale** (why) / **Alternatives considered** (what else, and why not). Resolve every `NEEDS CLARIFICATION` here.

5. **Phase 1, design and contracts.** With research settled, generate the design artifacts the size budget keeps. They share no evolving state, so write them in any order. Inline, one after another, is the default. Only when the documents are genuinely large *and* your host has subagents is it worth generating them concurrently, one subagent per document. The result is identical either way.
   - `<feature_directory>/data-model.md`: the entities this feature introduces or reshapes, with fields, relationships, validation rules drawn from the requirements, and any state transitions.
   - `<feature_directory>/contracts/`: the interface the feature exposes (API / CLI / schema, or a UI contract listing routes and the identifiers a consumer/test codes against). **Copy every identifier from the spec's Verbatim Constraints exactly. Never rename, recase, pluralize, or invent an identifier the spec already pinned: those exact strings *are* the contract.** Skip the directory only when the feature exposes no interface at all.
   After the documents return, re-check the Constitution Check against the final design.

   **`design` closes after `contracts/`, not after the last document you happened to write.** Record the `design` substep finish only once every Phase 1 artifact this size budget keeps is on disk.

6. **Capture the plan's reasoning** so the *why* survives the session (best-effort; JSON when you can produce it, bare text when not; skip silently if `python3` is unavailable):
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step plan --batch '{
     "decisions": [{"decision": "<what you chose>", "why": "<why>", "rejected": "<the alternative not taken>"}],
     "step_summary": {"summary": "<one-line rollup>", "key_finding": "<the most load-bearing thing you learned>"}
   }'
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --set approach="<2-3 sentence how-summary>"
   ```
   Record one `decisions` entry per genuine choice from Phase 0; skip trivia. These are additive and de-duped, so re-running them never duplicates. **One call, not one per item.** `--batch` takes the whole volley as a single JSON object and applies each writer additively, so the shared context file is read and rewritten once instead of once per entry. Emit one `--batch`.

**Output**: `<feature_directory>/plan.md` plus `research.md`, `data-model.md`, and `contracts/` when applicable.
<!-- /speckit-companion:node side-files -->
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

**The next step is `tasks`.** Dispatch `speckit.companion.tasks <feature_dir>`.

<!-- speckit-companion:part self-advance -->
## Self-advance: hand off to the next step

This is one step in the Companion pipeline. How the run continues depends on the environment you are running in; do not invoke a separate headless/deterministic run command for the everyday flow.

- **On an agentic CLI that keeps acting after a step finishes:** once this step's work is complete, dispatch the next step's `speckit.companion.*` command and keep going. The order is fixed and this step's own handoff names its successor, so there is no file to open to find out.
- **Pause at every review gate, and name the command that continues.** Where the workflow marks a `gate` (e.g. review-spec, review-plan), stop and wait for approval rather than running past it. When you stop, **name the next command literally**: "approve and run `speckit.companion.plan <feature_dir>`", not "approve to move to plan". Only continue once the gate is approved.
- **Nothing follows implement.** Implement's own final node writes `completed` through `write-context.py --mark-complete`, so the spec is already finished when this step ends. Do not dispatch `speckit.companion.mark-complete` afterwards: it is the manual recovery command and the workflow engine's terminal step, not a step a run adds for itself. There is exactly one writer of `completed`; never introduce a second.
- **Degrade gracefully on a one-shot environment.** If your environment runs one step and then stops, the handoff simply does not fire: finish this step, record its progress, and stop. The run stays valid and resumable, and the next step is triggered manually, by the developer or the companion panel. Completion likewise stays a manual action there.
<!-- /speckit-companion:part self-advance -->

**Pin the workflow identity in the same call that closes the step.** Record that this spec runs the **Companion** workflow, so the next dispatch is a Companion command and not a stock one. The shared writer defaults `workflow` to `speckit`, so without this the footer advance dispatches the stock successor. `--set` writes a plain field and appends no history, so it rides alongside `--advance`:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step <this step> --advance --by ai --set workflow=companion
```

Idempotent, and a required deterministic write. Skip only if `python3` is genuinely unavailable. This replaces the bare `--advance` the timing rules describe; run one or the other, never both.
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
