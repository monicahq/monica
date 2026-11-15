# Domain Docs

This repository keeps its domain language and its decisions in places that already exist. There is no root `GLOSSARY.md` and no `docs/adr/`, and none should be created: a second home for a term or a decision is how the two drift apart.

- **The glossary**: the section "The glossary" in `.claude/rules/translations.md`. It names the product nouns (vault, contact, template, field, possession type, and so on) and how each is said in every locale.
- **The principles**: `.specify/memory/constitution.md`. It governs every other rule, including WCAG 2.2 AA, the absence of per plan entitlements, and how the rules files relate to each other.
- **The decisions**: `specs/NNN-<name>/`, one folder per feature, holding the spec, the plan and the research with its reasoning. Read the specs that touch the area you are about to work in (`ls specs`, then `grep -ril '<term>' specs`).
- **The rules**: `.claude/CLAUDE.md` and `.claude/rules/`, which win over any skill default.

## Consumer rules

When your output names a domain concept (an issue title, a refactor proposal, a hypothesis, a test name), use the term as the glossary spells it. Do not drift to a synonym. ASD-STE100 applies: one word for one meaning.

If the concept you need is not in the glossary, either you are inventing language the project does not use (reconsider), or there is a real gap. A gap in a product noun is closed by adding a row to the glossary in `.claude/rules/translations.md`, never by creating `GLOSSARY.md`.

If your output contradicts a spec or the constitution, say so explicitly rather than silently overriding it:

> _Contradicts specs/068-laravel-pennant-feature-flags (flags never read the plan), but worth reopening because…_

A new decision worth recording goes into the spec of the feature that makes it, through the speckit workflow, not into a separate ADR.
