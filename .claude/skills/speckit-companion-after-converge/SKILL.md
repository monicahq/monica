---
name: speckit-companion-after-converge
description: Record the converge finish in .spec-context.json for the Companion GUI
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.after-converge.md
---

# Capture Converge Finish

Run this from the repository root to record that converge finished; if `python3` is missing, say the step was not recorded and carry on.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --hook finish --step converge
```