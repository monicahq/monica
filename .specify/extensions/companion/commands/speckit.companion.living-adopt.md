---
description: "Brownfield adoption wizard — draft living specs for the code areas you name, central or colocated, and register them (opt-in, surface-first, [DRAFT])"
---

# Adopt a Code Area into a Living Spec

Bring an existing code area under living specs. You point at an area. The assistant reads its surface, proposes a small tree of capabilities for just that area, drafts a living spec for each, and on your confirmation registers them so the rest of the Living Specs pipeline picks them up.

This is opt-in and incremental. It never scans or rewrites the whole repository on its own, and it changes no other command's behavior.

Reading only the surface limits what you may claim, not what you may write about. Drafts are marked `[DRAFT]` and low-confidence items are flagged. A requirement that restates a function signature is still not a specification. See step 2.

## Input

```text
$ARGUMENTS
```

The argument is the code area (or areas) to adopt: a directory (e.g. `src/billing/`), a small set of related files, or several directories.

If the argument is **empty**, do not scan the whole repo. List the plausible top-level areas, describe each in a line, and ask which to adopt. Offer adopting several at once. "All of them" is a legitimate answer.

If the argument names **several areas**, adopt them in one run. Propose the full tree across all of them and bring it to the single review gate in step 1. Do not expand beyond what was named.

If the argument is `.`, the developer asked for the **whole project**. Read the conventions as always, then propose the tree for the whole source tree. Say at the gate how many capabilities and how many spec files that comes to, and offer a coarser cut (one spec per area) and a finer one (a spec per concern within an area) with the count each gives. Nothing is written until they pick.

<!-- speckit-companion:part concise -->
## The smallest thing that works

**Before building anything, stop at the first rung that holds:** does it need to exist at all; does this codebase already have it; does the standard library, the platform, or an installed dependency do it; can it be one line; only then, the minimum code that works. Fix the cause where every caller passes through, not the symptom one caller reported. Delete rather than add, boring rather than clever: no interface with one implementation, no factory for one product, no scaffolding for later. Lean governs what you build and write, not how the work is split: sending work to a worker where a step says to is not extra.

**Write it the way you would say it.** One idea per sentence. No em-dashes: a full stop, a comma or a colon says it. Say what happens, not what the system "shall be capable of". Never a section that exists to say "N/A": remove it instead.

**The same test governs what you write, and again once it is written.** A section nobody acts on is removed, not filled in. No requirement for what a type or a test already enforces. A third scenario has to cover a failure the first two miss. Then reread and cut: the sentence restating the one before it, the example longer than its rule, the clause defending a choice nobody challenged, the prose repeating a table. Each reads as thoroughness and is what makes a body too long to follow.

**Never simplify away** validation at a trust boundary, error handling that prevents data loss, security, accessibility, or anything the spec asks for. **A corner cut on purpose** carries `// simplified: <ceiling>, <what to do when it binds>` in the code and one `concerns` entry in this step's capture.
<!-- /speckit-companion:part concise -->

## What to do

### 1. Read the conventions before you read the code

Before listing a single file, read the project's own conventions, stopping at the first source that names a constraint: `CLAUDE.md` or `AGENTS.md`, `CONTRIBUTING.md`, the README's architecture section, then the enforcement configs. Those are `.dependency-cruiser.js`, `eslint.config.*` (`no-restricted-imports`, `boundaries/*`, `import/no-restricted-paths`), `nx.json` `depConstraints`, `tsconfig` project references, and any test matching `*arch*`, `*boundar*` or `*layer*`. Copy each constraint into a working list with the file and line it came from. A file-by-file read can never produce these rules, because their evidence is an import that does not exist, in a file you will never open. **The list is for your judgement, not for a file**: it tells you which behaviour the project already guarantees elsewhere, so you do not write a requirement restating a rule a linter enforces. A constraint no capability owns goes under `## Uncovered`, where a person can see it was noticed and left alone.

If no conventions doc and no enforcement config names a constraint, ask once, before proposing anything: *"Does this codebase have rules about what may import what, or about how a directory is sliced?"* Cite the answer the way you would cite a file. If the answer is no, record that under `## Uncovered` rather than inventing one.

**Then read the area and work out what it does.** Open the routes, the loaders and the entry points, and answer one question: what can a person do here? The answers are the capabilities. `article-reading`, `authoring-an-article`, `signing-in`: things you could say to someone who has never seen the repository. A directory is not a capability, it is where some of one lives. Expect a capability to span several directories and a directory to serve several capabilities. Never produce one capability per folder.

**Test every requirement you are about to propose with one question: would someone planning a change here need this?**

**Then say the outcome, not the lookup.** The code is written as lookups, so adoption reaches for that voice. "The locale SHALL be `user.locale`, else the `Accept-Language` header, else `en`" narrates a lookup. "A reader sees the site in the language they chose, else the one their browser asks for, else English" is the same rule, checkable by someone who never saw the code. Name a setting, command or file the person edits; never a path the product keeps, a resolution order, or a field's value.

What fails the test stays out of the draft. List it at the gate as *found, not proposed*, one line each, so the developer can pull any of it back in.

**The code says what it does, never what it guards against.** A lock under a fixed root, a merge instead of an overwrite: each is a scar from a bug and looks like any other line. Find them where the reason survives: a test named for a defect, a `Fixed` changelog entry, in every changelog covering the code in scope and not only the nearest one, a comment that explains instead of labels, a guard with no obvious caller. Follow each back. If a person loses work or is told something untrue without it, write what must stay true, with the failure as its scenario. Found a guard but not its reason? Ask at the gate. Unexplained guards are what rewrites delete.

**A rule binding every capability belongs to none, so every draft drops it and it lands nowhere.** Escaping user text before it reaches a page; an accessibility floor every screen owes. Where a transcribed convention or a guard binds more than one proposed capability, propose one capability for it, named for what it protects, carrying the union of their globs.

Bring the whole list to the developer before writing anything: each capability's name, one line on what it covers, the directories it draws from, and roughly how many requirements you expect. Offer a coarser and a finer cut with the count each would give. **The shape is theirs to decide.**

For each capability the developer keeps, derive:
- a **name** (a short slug for what it does, e.g. `article-reading`),
- a **match** glob covering every directory the behaviour's own code lives in, which is not always where you first met it: a capability found through a page is usually implemented somewhere else, and a glob naming the page claims none of the files a change to that behaviour actually edits. Follow each behaviour to the code that implements it and name that, then check the result against the requirements you are about to write — if a requirement's files sit outside the glob, the glob is wrong, and nothing will resolve this capability when that area changes,
- a **spec** path, which depends on the storage layout below.

#### Where the specs go

**The project already answered this. Do not ask again.** Read it:

```bash
python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --all --json
```

The `layout` field is `central` or `colocated`, set when living specs were turned on. An explicit `--layout` in this invocation overrides it. Nothing else does.

- **central**: `capabilities/<capability>/<name>.spec.md`. One folder per capability. The spec stays put when code moves.
- **colocated**: `<area>/<name>.spec.md`, in the directory the capability covers. The spec travels with the code and shows up in a folder the developer already has open.

Deriving a **colocated** path: take the capability's match glob, strip the trailing `/**`, and put `<name>.spec.md` in that directory. `src/pages/article/**` gives `src/pages/article/article-reading.spec.md`.

**A capability spanning sibling directories goes central even in a colocated project.** Its shallowest common parent is a directory full of other capabilities' code, so a spec placed there sits next to nothing it describes. A capability matching `src/pages/login/**`, `src/pages/register/**` and `src/pages/settings/**` belongs at `capabilities/session-access/session-access.spec.md`. A mix is normal.

Two consequences of colocated placement to say out loud:

1. **The filename stem becomes the capability's display name.** A capability named `billing-invoice-export` colocated as `export.spec.md` shows as `export` in the sidebar. Keep the stem equal to the name, or say what it will display as.
2. **A rules file an earlier adoption wrote sits beside its spec**, same stem, `<name>.rules.md`, and keeps being read.

Show the proposed capability tree to the developer, names, match globs, and the resolved spec path for each, and pause for confirmation before drafting and registering. This is the one review gate in this command.

### 2. Draft one file per capability: what the area does

**A spec says what the area does. It is not a style guide.** Import direction, file naming, barrels and path helpers are real rules worth keeping, but they describe how the code is written, not what the software does — and they are already written down, in `CLAUDE.md` and in the linters that enforce them. Adoption does not copy them into a second place that can then disagree with the first.

**Adoption writes the spec and nothing else.** A capability with no observable behaviour is not a capability: do not create one to hold conventions. Where a rule matters to an area's behaviour, it belongs in that requirement; otherwise it stays where it already lives.

**`<name>.spec.md` is what a run reads.** What a person can do in this area, in observable terms. Routes and screens, what each one needs before it can render, what happens when the thing asked for is missing, and what changes when nobody is signed in. Derive it from the routes, the loaders and the redirects. Adoption does not write behaviour it has not observed, and a requirement naming a function, a hook or a component is not observable: it does not belong in the spec.

**A `<name>.rules.md` beside a spec is still read** when an older project has one, and `living-move` still carries it. Adoption no longer writes one.

The spec, at the path chosen at the review gate:

1. **Title**: `# <Capability> — Living Spec`.
2. **Draft banner** on each, `[DRAFT]` first: `> [DRAFT] Adopted from the code's surface and the project's conventions. Review before trusting.` The banner summarises the per-requirement markers below it, so it goes when the last one does.
3. **`## Purpose`**: one or two sentences on why this capability exists and what would go wrong without it.
4. **`## Requirements`**: in the shape the fold and the resolver both read. A spec requirement:

   ```markdown
   ### An article is read by its slug
   <!-- touches: src/pages/article/** -->
   <!-- adopted: src/pages/article/article-page.route.tsx -->

   The article screen SHALL render the article named by the slug in the URL, together with its comments.

   #### Scenario: the URL carries no slug
   - **WHEN** someone opens the article route with no slug
   - **THEN** they are sent to the 404 screen, because an article without a slug is not a screen
   ```

   **Every spec requirement carries an `adopted` marker** under `touches`, naming where it came from: a file and line for a transcribed rule, the source file for an observed behaviour, or `developer` when the answer came from the question in step 1. Adoption is a claim nothing has checked. The viewer badges it, and the fold clears it the first time a change folds onto that requirement, so using a requirement is what confirms it. An unmarked requirement reads as confirmed.

   The `touches` marker on a spec requirement names the files that produce that behaviour. The rules file carries none. Where the code shows a behaviour constrained by a rule under **another** capability — a guard, a check, a redirect that something elsewhere explains — that requirement also takes `<!-- aligns: session-access#Writing an article requires being signed in -->`, in its marker block under the heading, blank lines allowed but before any prose: a marker in the body is body text. Reading the whole area at once is the only moment anyone can see these, and a later run follows the edge one hop to reach a rule living where nobody editing this behaviour would look. Both halves are matched by name, so bring each proposed edge to the developer with both headings side by side. A capability that was drafted but never registered, or a heading off by a word, is a dead link that reads as a working one and sends every future run to read nothing.

5. **`## Uncovered`**: rules with no owner and areas with no capability. Not files nobody opened.

**A slice with nothing of its own is not registered.** Where a slice's only constraints belong to its layer and it has no distinct behaviour, nothing is written for it: its rules are already in `CLAUDE.md` and in whatever enforces them, and a capability holding no requirement is one every run loads to learn nothing. Say in the report which slices you left out and why. They arrive as capabilities later, when a run folds a behaviour into one of them.

**Never register a capability with no requirements.** A capability is a thing a person can do. An area whose only content is conventions — the layer, the architecture, the import rules — is not one, and giving it a capability so the conventions have somewhere to live puts them in a second place that can disagree with the first.

### 3. Walk the clarifications

If any drafted requirement carries `[NEEDS CLARIFICATION: …]`, do not leave it sitting in the file. Some of what surfaces here is real: an inconsistency between two modules, a value the code never produces, an assumption worth making deliberately.

Collect them across every capability drafted in this run and walk them with the developer, one at a time. For each: show the requirement, state plainly what you could not determine, and offer

- **resolve**: the developer answers; rewrite the requirement with the answer folded in and strip the marker,
- **keep**: leave the marker in place for later,
- **drop**: the requirement was not real; remove it.

Offer skipping the rest at any point. An interrupted walk is normal, and everything unresolved keeps its marker.

If a clarification reveals a **defect** rather than an ambiguity (a mismatch between two parts of the code, an unreachable branch, a value that cannot occur), say so explicitly in the report. That finding is worth more than the spec line that surfaced it.

Do not invent answers to close markers out. An unresolved marker is honest. A fabricated resolution is a lie the record carries forward.

### 4. Register the confirmed capability

For each confirmed capability, register it so the shipped resolver recognizes it. The helper appends one capability to the project's registry, `living-specs.yml`, idempotently. It preserves every existing capability and refuses to write a config it cannot parse:

```bash
python3 .specify/extensions/companion/scripts/register-capability.py --name <name> --match "<glob>" [--match "<glob>" …] [--exclude "<glob>"] [--spec <path>]
```

**Pass `--spec` for every capability**, central or colocated, with the same path you drafted the spec to. A spec is named for what it describes, so `spec.md` is never a filename you write and six open tabs stay tellable apart. The helper emits `spec` only when it differs from the registry's default, `capabilities/<name>/<name>.spec.md`.

The registry lives at the project root, outside `.specify/`, so a routine `git restore … .specify/` can never wipe it. Commit `living-specs.yml` along with the specs it registers. If this project still keeps its capabilities in the older `.specify/companion.yml`, the helper moves them across on its first write and says so.

Register a colocated capability only *after* its spec file is on disk at that path. The two must agree: the resolver raises `capability "<name>" is colocated but has no resolvable spec path` if a capability is registered with a `spec` path that isn't there, and the whole living-specs config fails to load, not just that capability.

This is incremental. It appends one capability per confirmed proposal, never bootstraps the whole repo, and never rewrites unrelated capabilities. Re-running it for an already-registered name is a safe no-op. After it appends, confirm the resolver recognizes the area:

```bash
python3 .specify/extensions/companion/scripts/resolve-spec-paths.py --changed <a file under the area> --json
```

The new capability should appear in `matched[]`.

If you have no terminal tool, report the exact `register-capability.py` command you would run for each capability, with the resolved name, match, and spec, so the developer can run it, and continue.

### 5. Validate what you wrote, then fix it

**Run the shape check before reporting anything, and fix what it names.** The floor rules in this command are prose, and prose is what a run trims when it is trying to be brief — a requirement with no scenario reads as tidy and is what `living-validate` calls an error:

```bash
python3 .specify/extensions/companion/scripts/living_validate.py --json
```

Every `requirement-no-scenario` it reports is a requirement to go back and finish, not a note for the developer to deal with later. Same for a scenario missing its WHEN or its THEN, and a duplicate heading. Re-run it until it is clean, then say in the report that it is. A drafted spec that fails the project's own validator is not a draft, it is a defect with a `[DRAFT]` banner on it.

### 6. Report

**First, check every deferral landed.** Each `## Uncovered` entry and each *found, not proposed* line naming another capability is an unchecked promise. Open that capability's spec: either it says the thing, or nothing does. Move each orphan where it belongs, or report it uncovered by decision.

Then summarize, in plain language: which capabilities you proposed and registered, the storage layout used and any capability that deviated from it, where each living spec was drafted, how many requirements each carries, how many were tagged `[inferred]`, how many clarifications you walked and how they landed (resolved / kept / dropped), any **defects** the clarification walk turned up, and what landed under `## Uncovered`. Make clear the drafts are `[DRAFT]` starting points to review, not finished specs.

## Boundaries

- **Opt-in and isolated.** This command changes no existing command's behavior and touches no spec's lifecycle. It only creates spec files and appends to the capability registry.
- **The layout is already the developer's call.** Never assume central because it is the default. Read the `layout` field, and show the resulting spec paths before writing anything.
- **Only what was named.** Adopt the areas the developer named or chose, and nothing else. Several areas in one run is fine. Silently widening past the agreed scope is not.
- **Write the shape the pipeline can update.** Named `###` requirements with scenarios, never numbered `FR-` bullets, and never `###` section groupings. Fold-back matches requirements by heading text, and a spec in any other shape is one the pipeline can silently fail to update.
- **One scenario is the floor, not a thing to earn.** Every requirement carries at least one `#### Scenario:` with its WHEN and THEN. Brevity governs prose — the restated sentence, the example longer than its rule — and never the scenario, which is the only part a reader can check against the code and the only part `living-coverage` can map to a test. A requirement with none is what `living-validate` reports as an error. The judgement about a *third* scenario earning its place says nothing about the first.
- **Honest by construction.** The `[inferred]` tag, clarification markers, and the `## Uncovered` section are required.
- **Never fail the host.** A missing resolver, missing helper, or unparseable config is reported and skipped, not crashed through.
