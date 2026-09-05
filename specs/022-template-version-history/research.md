# Research: Template version history

No `NEEDS CLARIFICATION` was carried out of the Technical Context. What follows are the decisions the plan rests on, each recorded once.

## Route and entry point

**Decision**: `GET settings/account/templates/{template}/versions`, named `settings.templateVersions.index`, handled by `TemplateVersionHistoryController@index`.

**Rationale**: `POST .../versions` is already `settings.templateVersions.create`, so the GET completes an existing name family on a URL that is free. Kebab-case URL, camelCase name, verb first, per `rules/code-style.md`.

**Alternatives considered**: `/history` (invents a second word for a thing the code calls a version); a panel inside the builder (the design draws a screen with its own back control).

## Reading the tree without N+1

**Decision**: one nested eager load on the template: `versions.pages`, `versions.assembly.sectionVersion.section`, `versions.assembly.sectionVersion.fields`.

**Rationale**: `Template::versions()` already orders `version_number` descending, `TemplateVersion::pages()` orders by position, and `assembly()` orders by page then position, so the ordering the screen needs comes from the relations. `assembly` is the row that names the exact `section_version_id`, which is what keeps the read frozen: going through `Section` instead would show today's section, not the one placed.

**Alternatives considered**: loading one version at a time on expansion (an extra round trip per version, for a tree that is small and already collapsed client-side); querying `SectionVersion` directly (loses the page grouping and the `title_override` on the pivot).

## The "added over its predecessor" summary

**Decision**: compare a version against `previous_version_id`, not against the newest. Count pages, sections and fields on each side and report only the positive differences, as counted phrases. A version with no predecessor says it is where the template starts.

**Rationale**: `previous_version_id` is what the version was actually branched from, and is what the header already names as "based on v2". Comparing against the newest would make an old entry's summary change every time somebody publishes.

**Alternatives considered**: a field-level diff naming which field was added (needs field identity across versions, which `fields.key` gives only within a section version, and is a bigger feature); no summary at all (the design shows one, and it is what makes the list scannable).

## The "new in this version" mark

**Decision**: a section row is marked when its `section_version_id` is not among the `section_version_id`s the predecessor version placed.

**Rationale**: it is an exact set membership on the assembly rows already loaded, so it costs no query. It catches both a section added outright and a section bumped to a later section version, which is what a reader wants to see.

**Alternatives considered**: comparing `section_versions.version_number` per section (misses a section that is new); marking against the newest version (same drift problem as the summary).

## Expanding a version and a section

**Decision**: render the whole tree, hide it with Alpine `x-data="{ open: false }"` / `x-show`, and toggle from a real `<button>` carrying `:aria-expanded`, copying `resources/views/app/settings/account/logs/_email-sent-row.blade.php`.

**Rationale**: that file is the project's existing expandable row, down to the chevron rotation and the focus ring, and reusing it keeps the screen consistent and satisfies Principle IX without inventing anything. `x-cloak` keeps the panel from flashing on load.

**Alternatives considered**: `<details>`/`<summary>` (free semantics, but the codebase uses it nowhere and it is hard to style to match); Alpine Ajax per expansion (a request for content already on the page).

## Dates and counted phrases

**Decision**: absolute dates through `isoFormat('LL')`, as `TemplatesViewModel` and `ShareTemplateViewModel` do. The builder's own "Saved …" line keeps `diffForHumans()`. Every count goes through `trans_choice()`.

**Rationale**: a version's publication date is a fact somebody may want to quote, so it reads better absolute; the draft's saved line is about recency and already reads relative in the builder. `rules/code-style.md` forbids `__()` for a phrase carrying `:count`, and `monica:localize --check` fails on it.

**Alternatives considered**: relative dates everywhere (an entry published two years ago reading "2 years ago" is worse for the question this screen answers).

## The builder link

**Decision**: `TemplateBuilderViewModel::hasHistory()` returns `$this->template->versions->count() > 1`, and `builder.blade.php` adds an `<x-link turbo>` after the existing **Edit** link, separated by the design's middot.

**Rationale**: `versions` is already loaded on that screen, so the check costs no query. Putting the condition in the view model keeps the Blade free of logic.

**Alternatives considered**: showing the link always (the author asked for it only when relevant).
