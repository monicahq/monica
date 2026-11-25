---
name: speckit-companion-before-step
description: Capture a spec-kit step's start into .spec-context.json for the Companion
  GUI
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: extension:companion
  version: 0.28.0
user-invocable: true
disable-model-invocation: false
---

# Companion Before Step Skill

# Capture Step Start

Run this from the repository root, passing the step that is about to run (specify, plan, tasks or implement) as `--step`; if `python3` is missing, say the step was not recorded and carry on.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --hook start --step <step>
```
