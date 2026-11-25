---
name: speckit-companion-after-specify
description: Capture the current spec-kit step into .spec-context.json for the Companion
  GUI
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: extension:companion
  version: 0.28.0
user-invocable: true
disable-model-invocation: false
---

# Companion After Specify Skill

# Capture Specify Finish

Run this from the repository root to record that specify finished; if `python3` is missing, say the step was not recorded and carry on.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --hook finish --step specify
```
