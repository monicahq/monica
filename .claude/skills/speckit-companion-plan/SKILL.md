---
name: speckit-companion-plan
description: Companion plan — implementation plan with research & design artifacts
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.plan.md
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
  - Skip the plan's blocks (calls, code, states, screens).
  - Generate `contracts/` only if the feature exposes an interface a consumer or test codes against.

This budget governs every step that follows. Where a later step would produce something the budget skips, omit it. Do not produce it and then delete it.
<!-- /speckit-companion:node size-budget -->
<!-- speckit-companion:node gather-context -->
1. Read `.specify/feature.json` for the feature directory. The step's start is already stamped, above. Load the feature spec (`<feature_directory>/spec.md`, or `<short-name>.spec.md` in a project written before this) and `.specify/memory/constitution.md` if present. Those are the inputs the plan must satisfy. **Read what `specify` recorded before you open a file.** `specify` writes what it read onto `.spec-context.json` under `context`: the code areas it looked at and the constraints it found there. Read those entries first. They name where the feature attaches, so open the files they point at instead of rediscovering them. They carry locations, not content, so you still open the files that matter, filling gaps in a map you were handed. If no `context` entries were recorded, investigate from scratch as below. **Plan against the words the person used.** The record holds the request this run started from under `request`, word for word. Read it beside the spec, and where the two differ, the request is what was asked.

Then **investigate the codebase** to understand where this feature attaches: the patterns it must follow (state/store, routing, persistence, component and test conventions) and the exact files it will touch.

**Before you open a source file, run this and do exactly what it prints:**

```bash
python3 .specify/extensions/companion/scripts/dispatch-briefs.py --feature-dir <feature_directory>
```

When it prints reader briefs, dispatch every one of them in a single message, unedited. That is not a judgement call: it holds in an auto run, for short features, and when specify already sent readers, because those mapped where the feature lands and these find the pattern to copy. When it prints `Working inline`, read the code yourself. Hand each worker the slice the load above already resolved for its area, so it reads code to fill the gaps rather than to relearn what is written down. Each worker returns a **distilled finding**: the pattern to copy, the concrete file paths, the conventions to match, and anything the code does that its slice does not account for. Never file contents. One area, or no subagent tool: read it yourself. Collect the findings as the research basis for the plan.
<!-- /speckit-companion:node gather-context -->
<!-- /speckit-companion:phase gather -->
<!-- speckit-companion:phase author -->
<!-- speckit-companion:node plan-doc -->
2. Create `<feature_directory>/plan.md` with these sections, in order. This is the full `normal`/`oversized` shape and the **size budget above governs**: at `simple` size it keeps only the Summary and skips the rest unless genuinely needed. Lead each section with prose. Reserve `inline code` for real identifiers (paths, types, packages), not ordinary nouns: a sentence that is mostly code spans is a rewrite.
   - **Summary**: 2–4 plain-language sentences giving the primary requirement plus the technical approach. If a stack choice genuinely isn't obvious from the codebase (a new language, a newly-added dependency, a non-default storage or test setup), name it in a sentence here. Otherwise don't restate the project's known stack.
   - **Project Structure**: the concrete source layout this feature touches, as a short tree of real directories/files, plus a one-line **Structure Decision**. Use the actual paths; do not leave placeholder option-trees in the output. *(Skipped at `simple` size per the budget.)*
<!-- /speckit-companion:node plan-doc -->
<!-- speckit-companion:node pick-blocks -->
   **Add a block to `plan.md` only where the change needs one.** Skip this at `simple` size. Decide each row, then run the command with the names that apply, or `none`, and write what it prints into `plan.md`.

   | Block | Write it when |
   |---|---|
   | `calls` | the change crosses functions or files |
   | `code` | one line needs a note to be understood |
   | `states` | the change adds or alters a lifecycle |
   | `screens` | the change alters what a person sees |

   ```bash
   python3 .specify/extensions/companion/scripts/plan-blocks.py --feature-dir <feature_directory> <names>
   ```
<!-- /speckit-companion:node pick-blocks -->
<!-- /speckit-companion:phase author -->
<!-- speckit-companion:phase check -->
<!-- speckit-companion:node constitution-check -->
3. **Constitution Check**: add a `## Constitution Check` section to `plan.md` as a table: one row per constitution principle with a PASS / justified-violation assessment. This is a gate before Phase 0 research, re-checked after Phase 1 design. If a violation is genuinely necessary, justify it in a short **Complexity Tracking** table (violation | why needed | simpler alternative rejected). Omit Complexity Tracking when there are no violations; ERROR on an unjustified gate failure.
<!-- /speckit-companion:node constitution-check -->
<!-- /speckit-companion:phase check -->
<!-- speckit-companion:phase wrap-up -->
<!-- speckit-companion:node side-files -->
4. **Phase 0, research first.** Write `<feature_directory>/research.md` before the Phase 1 docs, which build on its decisions. *(The size budget above governs: at `simple` size, fold the rationale into a short Key Decisions note in `plan.md` instead of a separate `research.md`.)* For each genuine unknown the plan leaves open (a stack or dependency choice the codebase doesn't already settle, an integration, or a significant design choice) record a short entry as **Decision** (what you chose) / **Rationale** (why) / **Alternatives considered** (what else, and why not). Resolve every `NEEDS CLARIFICATION` here.

5. **Phase 1, design and contracts.** With research settled, generate the design artifacts the size budget keeps. They share no evolving state, so once `research.md` is on disk run `python3 .specify/extensions/companion/scripts/dispatch-briefs.py --feature-dir <feature_directory> --docs` and do exactly what it prints: writer briefs are dispatched in a single message, unedited, however short the documents look; `Working inline` means you write them yourself.
   - `<feature_directory>/data-model.md`: the entities this feature introduces or reshapes, with fields, relationships, validation rules drawn from the requirements, and any state transitions.
   - `<feature_directory>/contracts/`: the interface the feature exposes (API / CLI / schema, or a UI contract listing routes and the identifiers a consumer/test codes against). **Copy every identifier from the spec's Verbatim Constraints exactly. Never rename, recase, pluralize, or invent an identifier the spec already pinned: those exact strings *are* the contract.** Skip the directory only when the feature exposes no interface at all.
   After the documents return, re-check the Constitution Check against the final design.

   **`design` closes after `contracts/`, not after the last document you happened to write.** Record the `design` substep finish only once every Phase 1 artifact this size budget keeps is on disk.

6. **Capture the plan's reasoning** so the *why* survives the session (best-effort; JSON when you can produce it, bare text when not; skip silently if `python3` is unavailable):
   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step plan --batch '{
     "decisions": [{"decision": "<what you chose>", "why": "<why>", "rejected": "<the alternative not taken>"}],
     "step_summary": {"summary": "<one-line rollup>", "key_finding": "<the most load-bearing thing you learned>"},
     "set": {"approach": "<2-3 sentence how-summary>"}
   }'
   ```
   Record one `decisions` entry per genuine choice from Phase 0; skip trivia. These are additive and de-duped, so re-running them never duplicates. **One call, not one per item.** `--batch` takes the whole volley as a single JSON object and applies each writer additively, so the shared context file is read and rewritten once instead of once per entry. Emit one `--batch`.

**Output**: `<feature_directory>/plan.md` plus `research.md`, `data-model.md`, and `contracts/` when applicable.
<!-- /speckit-companion:node side-files -->
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

**The next step is `tasks`.** Dispatch `speckit.companion.tasks <feature_dir>`.

<!-- speckit-companion:part self-advance -->
## After this step

Inside a Companion auto run, or when the input says `workflow engine step`, the orchestrator decides what runs next: skip this. Otherwise, once this step's work is done, read `.specify/extensions/companion/commands/reference/self-advance.md` and follow it.
<!-- /speckit-companion:part self-advance -->

**Pin the workflow identity in the same call that closes the step.** Record that this spec runs the **Companion** workflow, so the next dispatch is a Companion command and not a stock one. The shared writer defaults `workflow` to `speckit`, so without this the footer advance dispatches the stock successor. `--set` writes a plain field and appends no history, so it rides alongside `--advance`:

```bash
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --step <this step> --advance --by ai --set workflow=companion
```

Idempotent, and a required deterministic write. Skip only if `python3` is genuinely unavailable. This replaces the bare `--advance` the timing rules describe; run one or the other, never both.
<!-- /speckit-companion:node handoff -->
<!-- /speckit-companion:phase wrap-up -->

<!-- speckit-companion:part orchestrator -->
## Node hooks

A project can attach its own work around this command's nodes in `.specify/companion.yml`. **Only if that file declares `commands.<this-command>.hooks`**, read `.specify/extensions/companion/commands/reference/node-hooks.md` and follow it. Otherwise there are no hooks: skip this and run the command as written.
<!-- /speckit-companion:part orchestrator -->