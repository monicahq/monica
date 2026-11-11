---
description: "Capture the current spec-kit step into .spec-context.json for the Companion GUI"
---

# Capture Specify Finish

Run this from the repository root to record that specify finished; if `python3` is missing, say the step was not recorded and carry on.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --hook finish --step specify
```
