---
description: "Record a spec-kit bug stage's finish and outcome in the bug's .spec-context.json for the Companion GUI"
---

# Capture Bug Stage Finish

Run this from the repository root, with the slug the bug command just resolved as `--bug` and its stage (assess, fix or test) as `--step`; the outcome is read from the report it wrote. If you do not know the slug, or `python3` is missing, say the stage was not recorded and carry on.

```bash
python3 .specify/extensions/companion/scripts/write-context.py --hook finish --bug <slug> --step <stage>
```
