# Web contract

## `GET vaults/{vault}` (`vaults.show`, existing)

Query, all optional: `slice`, `person`, `type`, `page` (data-model, Filters).

| Request | Answer |
| --- | --- |
| Ordinary | The whole dashboard, the timeline drawn for those parameters |
| `X-Alpine-Request` with `X-Alpine-Target: dashboard-timeline` | `app.vaults._timeline` alone: `<section id="dashboard-timeline">` and its contents, nothing else run |
| `X-Alpine-Target: dashboard-timeline-page-{n}` | The entries of page `n` alone, inside that id, for `x-merge="append"` |

The section holds, and tests may assert:

- `<nav aria-label="Years">` of links to `vaults.show` with the slice in the address; the selected one
  carries `aria-current="page"`.
- A `<form method="get">` with the hidden `slice`, the person search, and `<select name="type">`;
  `x-target.push="dashboard-timeline"`.
- Month headings as `<button aria-expanded aria-controls>`.
- A polite live region naming the slice and its count after a swap.
- "Newer: …" and "Older: …" links, "Clear filters" link, all ordinary links to `vaults.show`.

## `GET vaults/{vault}/timeline/candidates` (`vaults.timelineContactCandidates.index`, new)

`VaultTimelineContactCandidateController@index`. Query: `query` (string). Answers a fragment of at most ten
`role="option"` items, each carrying the contact's uuid and name, drawn by `x-record-picker.candidates`, for people of the vault taking part in
at least one life event of it. Covered by `RouteProtectionTest` like every route.

## `POST vaults/{vault}/life-events` (`vaults.lifeEvents.create`, existing)

Unchanged, except the redirect to `vaults.show` adds `slice` (R10).
