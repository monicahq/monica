## States: the lifecycle, not the code

**Show a lifecycle the change adds or alters**, so a reviewer clicks through it before any code is written. Skip it at `simple` size, and when the change adds no state, one sentence says so.

Add a `## States` section to `plan.md`: at most 2 blocks, each at most 8 states on a grid of 4 columns by 3 rows, at most one `note:` line under each.

```states <the lifecycle, in a few words>
Draft: Edited, not sent yet. (start)
Sent: Waiting on a reviewer.
Held: Parked until picked up. (proposed)
Done: Merged. (final)
Draft -> Sent: submit
Sent -> Held: park (proposed)
Held -> Sent: resume (proposed)
Sent -> Done: approve
grid:
Draft | Sent | Done
.     | Held | .
```
note: <one plain line, if needed>

A state is `name: one sentence`, then any of `(start)`, `(final)`, `(proposed)`, or a last `shows <screen-name>` that points at a `screen` block of the plan. An arrow is `from -> to: label`, with `(proposed)` for one the change adds. Every state sits on the grid once, `.` is an empty cell, and cells split on `|`. **Every state is reachable from the start, and every one with no way out is `(final)`.** At most 10 arrows: draw the lifecycle's shape and what the change adds or alters, not every legal move, and say a move that applies from every state (like "mark done") once in the `note:` line.
