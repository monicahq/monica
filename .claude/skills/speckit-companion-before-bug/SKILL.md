---
name: speckit-companion-before-bug
description: Record a spec-kit bug stage's start in the bug's .spec-context.json for the Companion GUI
compatibility: Requires spec-kit project structure with .specify/ directory
metadata:
  author: alfredoperez
  source: companion:commands/speckit.companion.before-bug.md
  version: 0.29.0
user-invocable: true
disable-model-invocation: false
---

# Capture Bug Stage Start

Run this from the repository root, with the slug the bug command just resolved as `--bug` and its stage (assess, fix or test) as `--step`; if you do not know the slug, or `python3` is missing, say the stage was not recorded and carry on.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --hook start --bug <slug> --step <stage>
```