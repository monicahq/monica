# Fan-out: dispatching story phases to workers

Read this only when a user-story phase owns five or more files and you have a subagent tool.

**Dispatch one worker per user-story phase that owns five or more files. If you have a subagent tool, Claude Code's `Agent`/`Task` tool or your host's equivalent, you use it there.** Setup and Polish stay with you: Setup is trivial, Polish is cross-cutting. **In Foundational, before each wave, run `python3 .specify/extensions/companion/scripts/dispatch-briefs.py --feature-dir <feature_directory> --waves` and do exactly what it prints:** that wave's worker briefs, dispatched together and unedited, or the tasks to build yourself. Start nothing after that wave until its workers return and pass the join-line check below. Never split a story phase per task: the startup costs more than the task.

**Count the phase's own files line, and that alone decides it.** It holds in an auto run too: specify, plan and tasks having run in this same session is not a reason to build inline. Below five files a worker's startup is the whole cost, so build a phase under the threshold inline, in phase order, and say which phases you dispatched and which you kept.

**Read each story phase's files line, `Files:` or `Files owned by this phase:`. That is its ownership.** The tasks step gave every file one owner, so the story phases are disjoint by construction and you dispatch them all together. Two phases naming the same file is a task-list defect: say so, and run those two one after another. Give each worker its phase's task lines, that user story from `spec.md`, the plan's Structure Decision. Then ask it to read what it needs, write the code **and that story's tests**, run **only the test files its phase owns** (the full suite runs once, at the end), and return a distilled result only: what it built, the files it touched, and any test still failing. A worker must never return file contents.

```bash
# the worker, per task it finishes: append only, never fold
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --task <TaskID> --kind complete --by ai --did "<one line>" --files "<files>" --append
# you, the moment each worker's result returns
python3 .specify/extensions/companion/scripts/write-context.py --feature-dir <feature_directory> --materialize
```

Folding is a read-modify-write on the shared file, so two folders at once race. **Workers only ever append, and you do every fold**, in the foreground, one at a time.

**At each join line, check the workers' claims before crossing it.** A worker's report names files it touched and tests it ran; confirm the files exist and the test files are on disk before its result becomes the next phase's input. A claim that does not check out gets one re-run with the specific correction, and a second failure stops the step rather than building on it.

Then reconcile. Hand the type-check, and the lint only if the project actually has one in its `package.json` scripts, to one worker and take back only the verdict and the failing names. A lint nothing defines is not a check you can run. Fix any seam drift between workers. Then run `--materialize` once more as a backstop: it is idempotent, and it catches any finish whose fold was missed. `tasks.md` is owned only through `--materialize`.
