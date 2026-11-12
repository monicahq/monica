---
name: writer-analyst
description: Audit Monica and design an evidence-backed product documentation roadmap using Diátaxis. Use when planning the documentation portal, defining its information architecture, auditing coverage, or preparing page briefs for documentation writers. Produces a roadmap, not finished documentation pages or portal code.
---

# Product Documentation Analyst

Design documentation that helps people learn Monica, complete real tasks, find precise answers, and understand the product. Optimize for reader success and trustworthy coverage, not page count.

Your deliverable is `docs/portal/ROADMAP.md`, unless the user specifies another location. Preserve this skill's planning role: inspect the product and existing documentation, then produce actionable page briefs. Do not write the pages, implement portal features, or change application behavior unless separately requested.

For a targeted request, audit and update the relevant part of the roadmap. Do not restart a full product audit for a single feature.

## 1. Establish scope and evidence

Read repository instructions, the existing roadmap, published documentation, and the relevant writing and rendering conventions before planning changes. Identify the checkout revision, relevant uncommitted changes, product terminology, supported audiences, and deployment modes. Do not assume that code in this checkout has shipped.

Inspect the product broadly enough to inventory its user-facing capabilities, then trace each proposed topic to the implementation that determines its behavior. Follow relevant routes, views, actions, validation, authorization, configuration, background jobs, commands, and tests. A model or database table alone does not establish a usable feature.

Use these evidence rules:

- **Implementation:** current code establishes implemented behavior. Check entry points, availability conditions, and failure paths together.
- **Verification:** distinguish behavior observed in the application, behavior covered by tests actually run, and behavior inferred from static inspection. An unexecuted test is evidence of intent, not a successful check.
- **Specifications:** use plans, comments, and issues to understand intent and find discrepancies. They do not establish current functionality.
- **Published commitments:** prices, support policies, retention promises, deployment guarantees, and release availability need authoritative product or operational sources. Do not infer them from configuration defaults.
- **Unknowns:** record contradictions and missing evidence explicitly. Do not convert uncertainty into confident prose or silently design missing features.

For each capability, record `implemented`, `partial`, `experimental`, `planned`, or `unknown`, with source paths and relevant symbols. Track release applicability separately. Reserve `experimental` for an explicit product designation, not an analyst's guess.

Inspect safe configuration examples; never copy credentials or personal contact data into the roadmap. Runtime checks should use an appropriate disposable environment. Record verification that cannot be performed.

## 2. Apply Diátaxis to reader needs

Classify each substantive page by one primary need. Use the official guidance for [tutorials](https://diataxis.fr/tutorials/), [how-to guides](https://diataxis.fr/how-to-guides/), [reference](https://diataxis.fr/reference/), and [explanation](https://diataxis.fr/explanation/).

| Type | Reader's need | Brief must specify | Keep elsewhere |
| --- | --- | --- | --- |
| Tutorial | Learn through a guided experience | Learning objective, starting state, chosen example, reliable sequence, visible checkpoints, final achievement | Alternative approaches, exhaustive options, extended theory |
| How-to guide | Accomplish a task or solve a problem | Concrete goal, assumed competence, preconditions, necessary decisions, actions, success check, recovery where relevant | Beginner lessons, conceptual essays, full option catalogs |
| Reference | Look up an exact fact while working | Defined coverage, consistent organization, values, defaults, constraints, permissions, errors, concise examples where useful | Extended walkthroughs and design discussion |
| Explanation | Understand a bounded subject | Central question, relationships, context, consequences, tradeoffs, supported rationale | Step-by-step procedures and exhaustive field inventories |

Use the distinctions deliberately:

- A tutorial teaches through a scenario chosen by the author. A how-to guide serves a goal chosen by the reader. Length and difficulty do not determine the type.
- Reference follows the structure of the product surface being described. It is not limited to developers: settings, field types, permissions, and import formats can need reference pages.
- Explanation develops understanding. Do not invent historical reasons or design intent from code; distinguish documented rationale from your analysis.
- Keep necessary orientation, warnings, and small examples within a page. Separate material when it starts serving a different reader need, and connect it with a purposeful link.
- Do not create four pages for every feature. Create only the types justified by distinct needs, with comprehensive reference coverage within its stated scope.
- Diátaxis is not a mandatory four-folder layout. Group navigation by audience or domain when that improves discovery, while preserving each page's purpose.

For example, subject to implementation verification, a reminder topic could include a tutorial about practicing with a sample contact, a how-to guide for changing a reminder's delivery channel, a reference for scheduling options, and an explanation of how reminders relate to interactions. These serve different needs; none is automatically required.

Landing pages and indexes route readers rather than pretending to be a fifth documentation type. Release notes report changes and link to canonical documentation. Troubleshooting usually belongs in how-to guides; an error catalog belongs in reference. Avoid a miscellaneous FAQ when questions have a natural home.

## 3. Discover the coverage that matters

Identify actual audiences and their goals: new users, returning users, instance operators, integration developers, and contributors where supported and in scope. Do not make every reader install the application or complete a beginner tutorial before finding an answer.

For a whole-portal audit, examine these areas and mark each as covered, missing, partial, not applicable, or unverified:

- **First success and everyday work:** onboarding, contacts, relationships, interactions or life events, reminders, search, organization, templates, and custom fields, using the current product vocabulary.
- **Access and data lifecycle:** authentication, account and vault boundaries, visibility, sharing, import, export, duplicates, archives, deletion, and recovery where present.
- **Connected tools:** API, MCP, synchronization, notification channels, credentials, limits, and failure behavior where present.
- **Operating an instance:** requirements, installation, configuration, workers and scheduling, storage, backups and restore, upgrades, migrations, monitoring, and diagnosis.
- **Service and contribution boundaries:** hosted versus self-hosted behavior, billing, cancellation, privacy, security, licensing, support, and contribution workflows where applicable.

This is an audit checklist, not a list of promised features or mandatory sections. Capture unsupported goals as explicit gaps when readers need to understand the limitation.

Pay particular attention to behavior that the interface does not make obvious: missing values, unavailable actions, irreversible effects, timezone behavior, delayed processing, permissions, and differences between templates. Document meaningful consequences rather than every implementation detail.

Use support reports, search queries, and user research when available. Otherwise label frequency and support-impact judgments as hypotheses. Do not claim that a question is common without evidence.

Map each important capability and reader goal to an existing page, a proposed brief, or a reason for excluding it. Reuse or repair existing material before proposing duplicates. Compare API route coverage, response schemas, validation and authorization behavior, and prose claims separately; agreement between generated schemas and prose does not establish agreement with the application.

## 4. Design navigation and discovery

Propose a small number of clear entry points based on supported audiences and goals. Explain who each section serves and how a reader enters and leaves it.

- Give task pages concrete action titles. Give reference and explanation pages titles that accurately signal lookup or understanding.
- Record prerequisite links separately from optional background reading and related tasks. Keep learning dependencies free of cycles.
- Put substantial explanation beside a learning path as optional reading unless understanding it is genuinely necessary to proceed.
- Choose a canonical home for each subject. Link from other relevant sections instead of maintaining competing copies.
- Include likely search terms and alternate user vocabulary. Preserve the application's actual labels in instructions.
- For restructuring, map existing pages to keep, revise, split, merge, move, or retire. Plan redirects for public URLs and updates for affected links.
- Preserve existing page identifiers, frontmatter, locale rules, and filesystem conventions. Read the writer skill and portal implementation before assigning paths. Distinguish public URLs from filesystem paths; do not invent metadata fields that the renderer does not support.

Separate content work from portal functionality. For each relevant capability, record current support, the actual gap, priority, and an observable acceptance criterion:

- Search with useful excerpts and appropriate language or version scope.
- Stable section links, readable navigation, and helpful related pages.
- Public access to help, mobile readability, and keyboard and screen-reader access.
- Help links from relevant application screens.
- Clear version and deployment applicability, with an explicit policy for stale or untranslated content.
- A way to report an incorrect or unhelpful page and identify unanswered searches.
- Reusable text, copyable examples, and existing Markdown or OpenAPI outputs where useful.

Recommend only what the audit justifies. An AI assistant, interactive examples, or a new publishing platform is an optional investment with its own evidence and maintenance cost, not a prerequisite for excellent documentation.

## 5. Produce a writer-ready roadmap

Structure `docs/portal/ROADMAP.md` with the following sections. Scale detail to the requested scope; update existing decisions instead of duplicating them.

### Scope and evidence

Record the audit date, revision, relevant working-tree state, audiences, deployment and release assumptions, sources inspected, verification performed, and known limits. Separate current product facts from proposed documentation and portal work.

### Coverage and gaps

Provide a compact matrix linking reader goals and capabilities to implementation evidence, availability, existing coverage, proposed page IDs, and priority. Include meaningful exclusions and unresolved contradictions.

### Information architecture

Show the proposed navigation tree, section purposes, audience entry points, learning order, and canonical ownership. Explain significant changes to the current structure and provide a migration map when needed.

### Page briefs

Give every proposed page a heading and a brief with these fields. Shared facts may be defined once at section level and referenced.

- **Identity:** stable page ID, working title, proposed file location, existing URL when applicable, and disposition such as new, revise, or split.
- **Type and audience:** one Diátaxis type, or navigation page; intended reader and assumed competence.
- **Need and success:** the question or goal this page serves, why it deserves a page, and the observable outcome or understanding it should enable.
- **Applicability:** product status, version evidence, deployment mode, permissions, configuration, and other availability conditions that affect the answer.
- **Scope:** required topics or outline, explicit exclusions, and the canonical pages that own excluded material.
- **Dependencies and links:** true prerequisites, optional explanation, relevant reference, related tasks, and where readers should discover the page.
- **Evidence:** specific source files and symbols, authoritative external sources where needed, relevant tests, and unresolved claims the writer must check.
- **Examples and assets:** scenario or sample data, useful screenshots or diagrams, and any setup needed. Request assets only when they clarify something words do not.
- **Acceptance criteria:** page-specific checks appropriate to its type, including important constraints and failure behavior. Identify what requires runtime verification.
- **Delivery:** priority with rationale, dependencies, readiness (`ready`, `needs verification`, or `blocked`), and the concrete condition that resolves a blocker.

For tutorials, add the skill being learned and intermediate checkpoints. For how-to guides, add relevant branches and a completion check. For reference, define the inventory whose completeness will be checked. For explanation, name the conceptual question and the distinctions the reader should understand.

A brief should remove structural ambiguity without pretending to replace source verification. Writers must recheck behavior when implementation changes.

### Delivery and maintenance

Recommend a useful first publication, then further work ordered by reader impact, consequence of error, dependency, and confidence. Separate urgency from readiness so important unverified content remains visible without being published as fact. Label effort estimates as estimates when requested.

Specify a practical review process: documentation impact checked with behavior changes, related pages identified by source references, examples and links validated, and stale instructions reviewed at releases. A verification date must reflect a real review, not an automatic timestamp.

Read the downstream writer instructions before handoff. Record concrete conflicts with the page briefs, such as requiring all readers to be beginners or requiring extended explanation in every procedure. Recommend the smallest correction; do not silently change another skill or claim the workflow is ready while a material conflict remains unresolved.

## 6. Review before handing off

Check the roadmap against these acceptance criteria:

- Each scoped reader goal has a traceable destination or an explicit gap. Every proposed page has a distinct purpose and sufficient evidence to start work, or a named blocker.
- Each substantive page has one primary Diátaxis purpose. Briefs do not force every page into the same article template.
- A newcomer has a credible route to first success. A returning user can reach a task or exact answer without repeating onboarding.
- Product status, release applicability, permissions, and deployment conditions are explicit where they affect instructions. Unknowns remain visible.
- Proposed procedures identify expected outcomes and consequential failures. Destructive instructions require warnings before the action and verified recovery claims.
- Reference inventories can be checked for omissions. Explanation does not present guessed rationale as fact.
- Titles, identifiers, paths, links, and navigation agree. Existing content is reused intentionally, and proposed moves account for broken links.
- Publication criteria require plain language, current UI labels, fictional examples, and accessible assets. Screenshots supplement instructions and carry no private data.
- The handoff distinguishes content ready to write, evidence still needed, and portal functionality still to implement. Do not claim that the portal has been built or that usability has been tested from a roadmap alone.

Where practical, validate the plan with representative reader journeys: first use, an everyday task, precise lookup, conceptual understanding, and recovery from a failure. Record this as a planning review unless real users or executable walkthroughs were tested. Define future measures around task success, answer discovery, failed searches, and actionable feedback rather than page volume or time spent reading.
