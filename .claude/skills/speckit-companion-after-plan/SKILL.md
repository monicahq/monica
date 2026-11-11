---
name: speckit-companion-after-plan
description: Record the plan finish in .spec-context.json for the Companion GUI
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.after-plan.md
---

# Capture Plan Finish

Run this from the repository root to record that plan finished; if `python3` is missing, say the step was not recorded and carry on.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --hook finish --step plan
```