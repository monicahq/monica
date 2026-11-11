---
description: "Record a step's start from spec-kit's prompt-submitted event"
scripts:
  py: scripts/write-context.py --event user_prompt_submit
---

# Record Step Start On Prompt

spec-kit runs this on its own when a prompt is submitted; nobody types it.
