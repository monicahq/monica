---
name: speckit-companion-amend
description: Edit the active spec in place when you change your mind after plan or implement, record the change, and re-plan only when a requirement was added or removed
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.amend.md
  version: 0.29.0
user-invocable: true
disable-model-invocation: false
---

# Amend Spec

Change the active spec in place. This command edits `spec.md` with the change you describe,
records it in `.spec-context.json`, and re-plans only when the requirements changed.

<!-- speckit-companion:part command-spelling -->
## Name every command the way this project registers it

Commands are named in dot form throughout this body, `speckit.companion.plan`, because that is their canonical id, and without a leading slash, because the spelling a host actually registers is not always this one. Claude Code installs `/speckit-companion-plan`. Look at how the commands are installed in this project, under the agent's own commands or skills directory, and use that spelling every time you name one to the developer or dispatch one yourself. A dotted name typed into a host that registered dashes resolves to nothing at all.
<!-- /speckit-companion:part command-spelling -->

## Prerequisites

- Verify Python is available by running `python3 --version`.
- If `python3` is not available, warn the user and skip:
  `[companion] Warning: python3 not detected; skipped amend`.
  Do not fail the host command.

## Execution

1. Resolve the feature directory. Use the one in the input, else the `feature_directory` in `.specify/feature.json`. If it has no `spec.md`, stop and tell the user to run `specify` first.

2. Read the requirement list (the `FR-` lines) in `spec.md` before you touch it.

3. Edit `spec.md` in place with the change the user described. Never start a new spec: never mint a folder and never run `mint-spec-dir`. The change belongs to this spec.

4. Read the requirement list again and compare. A requirement added or removed means re-plan; wording only means no re-plan.

5. Re-plan only when a requirement was added or removed. First point `.specify/feature.json` at this spec by writing `{"feature_directory": "<feature_directory>"}`, because plan and converge read their target from it. Then invoke `speckit.companion.plan <feature_directory>`, then `speckit.converge <feature_directory>`. For wording only, run neither. If either fails, record a `--concern` that names the step and says the plan or tasks are now behind the spec.

6. Record the change from the repository root, after any re-plan, with `replanned` saying whether plan actually ran:

   ```bash
   python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --amend '{"what": "...", "why": "...", "by": "you", "replanned": true}'
   ```

   Use `"by": "you"` when the user asked for the change and `"by": "ai"` when you chose it. Keep `what` and `why` to one short sentence each.

7. Never write `status` or `currentStep`, and never start a new step yourself. The writer refuses a backward move, and the plan and converge commands record their own steps. If the spec's status is `completed`, converge's new tasks are recorded as a concern only. Tell the user to reopen the spec with Set status in VS Code before implementing them.

8. When a requirement was removed, end with a keep or remove question for the code that implemented it.
   - Attended: ask the user, then record the answer, keyed by the removed requirement's number so a later answer replaces it. For a removed FR-004, either:

     ```bash
     python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --decision '{"decision": "Keep the CSV export code", "id": "amend-keep-FR-004", "by": "you"}'
     python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --decision '{"decision": "Remove the CSV export code", "id": "amend-keep-FR-004", "by": "you"}'
     ```

   - Unattended (the prompt or `.spec-context.json` says `unattended: true`): do not ask. Record a `--concern` that names the code and says the requirement is gone.

## Output

```text
Amended <feature_directory>/spec.md: <what>.
Requirements: <added|removed|wording only>  →  <re-planned | no re-plan>
Recorded in .spec-context.json.
```

- Completed spec → add: `Reopen with Set status in VS Code to build the new tasks.`
- Removed requirement → add the keep or remove question, or the recorded concern.

## Graceful Degradation

Best-effort: if `python3` is missing or the feature directory cannot be resolved,
warn to stderr and stop. Never fail the host command.