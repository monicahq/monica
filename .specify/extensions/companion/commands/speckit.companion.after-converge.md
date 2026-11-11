---
description: "Record the converge finish in .spec-context.json for the Companion GUI"
---

# Capture Converge Finish

Run this from the repository root to record that converge finished; if `python3` is missing, say the step was not recorded and carry on.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --hook finish --step converge
```
