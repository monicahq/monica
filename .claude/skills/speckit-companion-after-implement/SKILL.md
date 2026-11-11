---
name: speckit-companion-after-implement
description: Record implement progress from tasks.md in .spec-context.json for the Companion GUI
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.after-implement.md
---

# Capture Implement Finish

Run this from the repository root to sync implement progress from tasks.md; if `python3` is missing, say the step was not recorded and carry on.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --hook finish --step implement
```