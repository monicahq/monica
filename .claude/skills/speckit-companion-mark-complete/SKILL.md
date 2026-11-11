---
name: speckit-companion-mark-complete
description: 'Mark the active spec completed by hand (writes status: completed)'
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.mark-complete.md
---

# Mark Spec Complete

Run this from the repository root to mark a spec that finished implement as completed (a clean converge already does it on its own); if `python3` is missing, say the spec was not marked and carry on.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --mark-complete --by ai
```