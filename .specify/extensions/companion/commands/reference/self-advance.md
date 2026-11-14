# After a step: hand off to the next one

Read this when a step finishes outside a Companion auto run.

This is one step in the Companion pipeline. How the run continues depends on the environment you are running in; do not invoke a separate headless/deterministic run command for the everyday flow.

- **On an agentic CLI that keeps acting after a step finishes:** once this step's work is complete, dispatch the next step's `speckit.companion.*` command and keep going. The order is fixed and this step's own handoff names its successor, so there is no file to open to find out.
- **Pause at every review gate, and name the command that continues.** Where the workflow marks a `gate` (e.g. review-spec, review-plan), stop and wait for approval rather than running past it. When you stop, **name the next command literally**: "approve and run `speckit.companion.plan <feature_dir>`", not "approve to move to plan". Only continue once the gate is approved.
- **Converge follows implement; a clean converge completes the spec.** Implement ends at `implemented`. Dispatch `speckit.converge` next: its after-converge hook writes `completed` once every task, including any converge added and built, is checked. Do not dispatch `speckit.companion.mark-complete`: it is the manual command, not a step a run adds for itself. There is exactly one writer of `completed`; never introduce a second.
- **Degrade gracefully on a one-shot environment.** If your environment runs one step and then stops, the handoff simply does not fire: finish this step, record its progress, and stop. The run stays valid and resumable, and the next step is triggered manually, by the developer or the companion panel. Completion likewise stays a manual action there.
