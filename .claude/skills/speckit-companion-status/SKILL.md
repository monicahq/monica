---
name: speckit-companion-status
description: Report where the active spec stands — current step, status, recorded decisions, and the next action — from .spec-context.json
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.status.md
---

# Spec Status

Print where the active spec stands: the current step, its status, the decisions recorded so far, and the next action. A script reads the run record and writes the answer, so it reads the same every time. **Read-only**: this command never writes `.spec-context.json`.

<!-- speckit-companion:part command-spelling -->
## Name every command the way this project registers it

Commands are named in dot form throughout this body, `speckit.companion.plan`, because that is their canonical id, and without a leading slash, because the spelling a host actually registers is not always this one. Claude Code installs `/speckit-companion-plan`. Look at how the commands are installed in this project, under the agent's own commands or skills directory, and use that spelling every time you name one to the developer or dispatch one yourself. A dotted name typed into a host that registered dashes resolves to nothing at all.
<!-- /speckit-companion:part command-spelling -->

## Execution

Run this from the repository root and print its output exactly as it comes, in a code block. Add nothing: no summary, no reading of the record yourself, no next-step advice beyond what it prints.

```bash
python3 .specify/extensions/companion/scripts/status-context.py --plain
```

Add `--feature-dir specs/<NNN>-<slug>` when the user named a spec. If `python3` is missing, print `[companion] python3 not found; status skipped.` and stop.