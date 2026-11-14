# Node hooks

Read this only when `.specify/companion.yml` declares hooks for the running command.

The running command is assembled from ordered **nodes**. A project attaches its own work around any node in `.specify/companion.yml`, and you are the runtime. Like the rest of the pipeline it must **never fail the host command**: degrade and continue.

**Find the hooks.** Look up `commands.<this-command>.hooks`, whose `before` and `after` anchors are keyed by a node id from this command's order. Run a node's `before` hooks immediately before its work and its `after` hooks immediately after, several at one anchor in declared order. An absent, empty, malformed or unparseable file means no hooks: run the shipped command unchanged, silently when it is absent and with one short warning when it is broken.

**Hook types:**

- `{ type: command, run: "<shell>" }`: run it with your terminal tool, then continue. Without a terminal tool, report the command you would have run rather than pretending.
- `{ type: prompt, text: "<instruction>" }`: act on the text before moving on.
- `{ type: node, ref: <id> }`: carry out `.specify/companion/nodes/<id>.md` as part of this command. A missing `ref` file is a real misconfiguration: report it and stop rather than silently skipping.

**Background hooks.** Any hook may add `background: true`: start it and continue without waiting, detaching a `command` (`&`, `nohup … &`) and not blocking on a `node` or `prompt`. Report its result whenever it lands. Never background anything that writes `.spec-context.json`, meaning the timing and capture calls: they read-modify-write a shared file, so two at once lose an update. Background is for slow side-effects like a test run or a build, not for bookkeeping.

A hook anchored to a node this run does not include, because a recipe dropped it, warns once and is skipped. A hook whose own work fails is reported and the pipeline continues, unless the failure clearly makes the rest unsafe.
