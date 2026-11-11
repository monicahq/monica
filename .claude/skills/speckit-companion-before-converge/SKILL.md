---
name: speckit-companion-before-converge
description: Record the converge start in .spec-context.json for the Companion GUI
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.before-converge.md
---

# Capture Converge Start

Run this from the repository root to record that converge started; if `python3` is missing, say the step was not recorded and carry on.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --hook start --step converge
```