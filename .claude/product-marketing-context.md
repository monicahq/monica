# Product Marketing Context

*Last updated: 2026-08-11*

> This is a repository-derived V1. It separates the product promised by `spec/spec.md` from what the current code visibly implements. Customer interviews, pricing details, competitor research, conversion targets, and quantitative proof still need validation.

## Product Overview

**Product name:** Monica. “Erica” is the code name for Monica v3 and should normally stay out of customer-facing copy.

**One-liner:** Monica is an open-source place to document your life and the people in it, structured your way.

**What it does:** Monica combines a private people directory with a day-by-day journal. People can record the details, relationships, dates, activities, notes, and reminders they do not want to forget, while defining their own templates, sections, fields, lists, and custom types of things to track. An activity entered once belongs both to the journal for that day and to the histories of the people involved.

**Product category:** Personal relationship manager / personal CRM, with personal knowledge management and journaling capabilities. “Personal relationship manager” is clearer and warmer than “CRM” for primary messaging; “personal CRM” remains useful for search and comparison.

**Product type:** Open-source, mono-user web application; available as a hosted service or free self-hosted software. The first release is responsive across desktop, tablet, and phone. Native iOS and Android applications are later-stage product direction.

**Business model:** Self-hosting is free and unlimited. The specification defines a hosted free offer plus a one-time purchase for full access, with no subscription. The current repository sets a hosted free storage allowance of 100 MB, but the final purchase price and precise hosted entitlements are not documented and must not be invented.

**Current product status:** The repository is under active development. Account and security foundations, vaults, contacts, configurable entity types, value lists, and the template builder are represented in current routes and tests. The journal, relationships, groups, reminders, search, marketplace, private sharing, complete public API, MCP server, webhooks, modules, and native mobile apps are specified product scope but must not be marketed as shipped until verified in code.

## Target Audience

**Target customers:** Individuals who intentionally keep context about the people and events in their lives, especially those who find a standard contacts app too shallow and a conventional CRM too commercial or rigid. Strong early segments likely include privacy-conscious and self-hosting users, open-source adopters, people maintaining large or complex personal networks, caregivers and family historians, community builders, and highly structured personal-knowledge-management users. These segments are hypotheses pending customer validation.

**Decision-maker:** The individual who will use and own the data. For self-hosting, the same person—or a trusted technical helper—also chooses, deploys, and maintains the software.

**Primary use case:** Keep meaningful, usable context about people and shared experiences without relying on memory or forcing that information into someone else’s predefined schema.

**Jobs to be done:**

- Remember what matters about people: what they are going through, important dates, family context, preferences, promises, and when we last connected.
- Reconstruct the history of a person or a period of life without entering the same event in multiple places.
- Organize personal information according to one’s own life, vocabulary, and level of detail.
- Keep sensitive personal records private, portable, and under the user’s control.
- Extend or automate the system without being locked into a built-in AI or a closed vendor workflow.

**Use cases:**

- Maintain separate “Family,” “Friends,” and “Work” vaults so contexts do not bleed into one another.
- Record a lunch, call, trip, or important conversation once and see it in both the daily journal and each participant’s history.
- Track details a contacts app did not anticipate, such as a child’s allergy, a friend’s shoe size, or a family member’s medical follow-up.
- Model nuanced human relationships, including direction, uncertainty, qualifiers, and changing periods.
- Track related non-person objects such as animals, companies, recipes, cars, or a wine cellar with custom entities.
- Build or install reusable templates and sections rather than starting every structure from scratch.
- Self-host for full control, or use the hosted version without taking on operations.
- Connect scripts or compatible assistants through the planned API, MCP server, and webhooks.

## Audience Profiles

Monica is a consumer, mono-user product rather than a B2B buying committee. These profiles describe likely user motivations and are not yet research-validated personas.

| Profile | Cares about | Challenge | Value we promise |
|---|---|---|---|
| The thoughtful friend or family member | Being present and remembering what matters | Important human context fades or stays scattered across messages and memory | One private place that helps preserve the details and history behind relationships |
| The personal archivist | Accurate, connected records of people and life events | Notes, photos, dates, and relationships live in disconnected tools | A directory and journal that become two views of the same life record |
| The customizer | A system that matches how they think | Contact apps impose shallow fields; flexible databases require building everything | Deep personalization with a usable default structure and reusable building blocks |
| The privacy-conscious self-hoster | Ownership, portability, inspectable software, no recurring fee | Cloud-only tools create dependence and uncertainty about sensitive data | Open-source software that can be self-hosted free and unlimited |
| The automation-minded user | APIs, interoperability, and tool choice | Closed tools limit capture, import, export, and automation | A product designed so interface capabilities can also be exposed through an API and MCP |

## Problems & Pain Points

**Core problem:** People forget meaningful details about the people around them because the information is either never captured or is fragmented across memory, contact cards, calendars, messages, notes, journals, and spreadsheets.

**Why alternatives fall short:**

- Standard contacts apps store coordinates and a few predefined facts, not a living history or the user’s own kinds of information.
- Traditional CRMs frame relationships as pipelines, deals, and follow-ups; they feel transactional for personal life.
- Notes apps and spreadsheets are flexible but require manual structure, linking, duplication, and maintenance.
- Journaling apps organize life by day but do not naturally build an enduring record for each person.
- Rigid personal CRMs guess in advance what everyone wants to record, leaving much of the interface unused while still omitting personally important fields.
- Closed hosted services make privacy-conscious users dependent on a vendor’s pricing, continued operation, export quality, and product decisions.

**What it costs users:** Lost context, missed occasions and promises, weaker follow-through, duplicated capture, time spent searching across tools, and the recurring work of maintaining a system that never quite fits.

**Emotional tension:** “I care about this person; why can’t I remember the things that matter?” Users may feel guilt after forgetting, anxiety about losing memories, frustration with impersonal tools, and discomfort placing intimate information in software they do not control.

## Competitive Landscape

This landscape is category-level and repository-derived. Named competitors and win/loss claims require current market research and customer evidence before publication.

**Direct:** Personal CRMs and personal relationship managers — solve a similar remembering-and-follow-up problem, but often impose a fixed contact model, emphasize networking workflows, or lack the linked journal, self-hosting, and structural personalization described for Monica.

**Secondary:**

- Contact managers and address books — convenient and familiar, but too shallow for personal history, nuanced relationships, and custom information.
- Journaling apps — strong chronological capture, but weak at turning repeated appearances into a durable record of a person.
- Flexible databases and personal knowledge bases — structurally powerful, but make users design and maintain relationships, timelines, reminders, accessibility, and record behavior themselves.

**Indirect:**

- Notes, spreadsheets, calendars, inboxes, and messaging search — already available and flexible, but fragmented and dependent on manual retrieval.
- Memory and informal social habits — require no new system, but fail unpredictably and become harder as relationships and years accumulate.
- Social networks — hold social history, but are designed for broadcasting and engagement rather than private, user-owned documentation.

## Differentiation

**Key differentiators:**

- A people directory and personal journal are two views of the same underlying activities, eliminating duplicate entry.
- Users define their own templates, pages, sections, fields, value lists, activity types, and custom entities.
- Human relationships are modeled as a first-class system with two-way reading, multiple concurrent relationships, uncertain or imprecise facts, qualifiers, and time periods.
- Published structures are versioned and frozen; installed structures never update without explicit acceptance.
- Templates and individual sections can be shared through a moderated marketplace and are copied on installation, remaining under the installer’s control.
- Vaults keep different parts of life watertight while the product remains deliberately mono-user and private.
- The same product can be hosted or self-hosted; self-hosting is free and unlimited, and hosted full access is designed as a one-time purchase rather than a subscription.
- WCAG 2.2 AA accessibility and multilingual behavior are product-wide delivery conditions, including for user-generated record structures.
- Every interface capability is intended to be available through a public API and official MCP server, with outbound webhooks and no imposed built-in AI.
- The open-source core is designed for community modules with explicit safeguards and extension points.
- Deletion, updates, and structural changes are explicit, traceable, and recoverable wherever the product can preserve data.

**How Monica does it differently:** Monica supplies a small usable skeleton, then lets each person redefine everything around stable concepts such as contacts, activities, relationships, groups, and vaults. Flexibility is contained through immutable published versions, explicit migrations, preserved history, and user-controlled updates.

**Why that is better:** Users get the fit of a system they designed without losing predictability, history, portability, or a quick starting point. The product adapts to the user’s life instead of asking the user to adapt their life to the product.

**Why users should choose Monica:** Choose Monica when personal relationships deserve more structure than a notes app, less commercial framing than a CRM, more personalization than a contacts app, and more ownership than a closed cloud service.

## Objections

These are anticipated objections, not objections proven through sales or interview data.

| Objection | Response |
|---|---|
| “This sounds like too much work to maintain.” | Start with a minimal supplied structure. Capture only what matters, and enter shared activities once so they appear in both the journal and people’s histories. Nothing in onboarding is required. |
| “Recording information about people feels invasive.” | Monica is a private personal record, not a social network or surveillance product. The product should encourage relevance and respect, lets users compartmentalize data, protects sensitive fields from casual exposure, and makes export and deletion explicit. Ethical-use guidance still needs to be developed. |
| “I can already do this in Notes, Notion, or a spreadsheet.” | Those tools can store the information, but Monica supplies the people, relationship, activity, reminder, history, search, privacy, and timeline semantics so the user does not have to build and maintain them. |
| “A customizable system will become chaotic.” | Published structures are frozen, records stay on exact versions, and changes happen through drafts and explicit upgrades. Another user or the system never silently changes an installed structure. |
| “Self-hosting is too technical.” | Self-hosting is an option for people who want it, not a requirement. The hosted version offers the same product without server maintenance. |
| “Can I trust a young product with important data?” | Be candid about development status. Lead with open source, exportability, traceability, conservative deletion, and the option to self-host; never claim maturity, uptime, or capabilities that are not yet evidenced. |

**Anti-persona:** Monica is not a good fit for teams that need shared workspaces, roles, permissions, collaborative editing, sales pipelines, or enterprise administration. It is also a poor fit for someone who wants passive, fully automatic relationship tracking with no intentional capture, or someone looking for a public social profile or broadcast network.

## Switching Dynamics

**Push:** Forgetting important details; losing context across apps; rigid fields; duplicating events between a journal and contact notes; paying indefinitely for a closed service; or maintaining a fragile home-built database.

**Pull:** A private directory plus journal, total structural personalization, open source and self-hosting, one-time hosted purchase, nuanced relationship modeling, explicit versioning, data portability, and future integration through API/MCP/webhooks.

**Habit:** Existing information is already in contacts, notes, spreadsheets, calendars, or another Monica version. Those tools are familiar, require no migration today, and may feel “good enough” until an important detail is missed.

**Anxiety:** Migration effort, the discipline required to keep records current, privacy and ethics, product immaturity, learning a flexible builder, self-hosting maintenance, and uncertainty about whether the product will remain supported. Import previews, sensible defaults, honest status communication, clear privacy controls, and reversible changes should reduce these concerns.

## Customer Language

The following language comes from product copy and founder narrative in the repository, not external customer interviews. Preserve its plainness, but do not label it as customer testimony.

**How people may describe the problem:**

- “I keep forgetting the things I actually wanted to remember about people.”
- “Who was going through a hard time?”
- “Whose kid had just been born?”
- “Which friend had I not called in a year?”
- “The thing I keep losing track of.”

**How the product describes the solution:**

- “Monica is where you put all of that.”
- “It is not a social network and there is nothing to broadcast.”
- “It is your own record, kept for you.”
- “A sealed compartment for one part of your life.”
- “Beyond people, a vault can hold animals, companies, cars, anything you want to keep track of.”
- “You decide what those things are called and where they appear.”
- “Search people, notes, anything…”

**Words and phrases to use:**

- Remember what matters
- Document your life and the people in it
- Structured your way
- Your own record
- Private by default
- People, not contacts, in broad marketing copy
- Journal, history, relationships, activities
- Vault / one part of your life
- Open source
- Self-hosted
- One-time purchase / no subscription, once final pricing is confirmed
- Explicit, reversible, under your control
- “Write down” and “keep track of” for approachable task language

**Words and phrases to avoid:**

- “Open source HR” — stale positioning that conflicts with the current product specification
- “Customer,” “lead,” “deal,” “pipeline,” and other sales-CRM language for personal users
- “Second brain” unless tested with customers; it is broad and overused
- “AI-powered” — Monica deliberately does not impose built-in AI
- “Automatically knows” or “effortless” — core capture is intentional, and automatic journal feeding is deferred
- “Collaborative,” “team workspace,” “family account,” “roles,” or “permissions” — the product is mono-user
- “Always up to date” — externally sourced structures never update without acceptance
- “Military-grade,” “unbreakable,” “100% private,” or other unprovable security absolutes
- “Complete,” “available now,” or “ships with” for any specified but unimplemented feature

**Glossary:**

| Term | Meaning |
|---|---|
| Monica | The customer-facing product name |
| Erica | Internal code name for Monica v3 |
| Account | One person’s personal configuration and billing space |
| Vault | A watertight compartment for one part of life, such as Family, Friends, or Work |
| Contact | A person being documented; use “person” in most broad marketing copy |
| Me | The unique contact representing the user |
| Entity | A user-defined type of non-person object, such as an animal, company, recipe, or instrument |
| Activity | Something that happened on a date, optionally involving people or entities; the building block of the journal |
| Journal | The day-by-day view of activities; the counterpart to a person’s record and timeline |
| Relationship | A structured, bidirectional human link between two contacts |
| Group | A dated set of people, such as a household, couple, social circle, family, or team; separate from relationships |
| Template | The complete versioned definition of a contact, entity, or activity record |
| Page | An ordered navigation and presentation unit within a template version |
| Section | A reusable thematic block of fields that can also be shared independently |
| Field | One typed piece of information inside a section |
| Value list | A reusable account-level list of choices used by fields |
| Private share | A revocable, limited, read-only link to a record or vault; not collaboration |
| Module | An extension that adds behavior or capability; distinct from a template, which configures existing capability |

## Brand Voice

**Tone:** Warm, thoughtful, calm, candid, and humane. Serious about privacy and accessibility without sounding institutional or alarmist.

**Style:** Plainspoken and conversational. Lead with a recognizable human moment, then explain the product concretely. Prefer short sentences and specific examples over category jargon or sweeping promises. Explain consequences before technical mechanisms. Speak in the first or second person when it makes the relationship feel direct and personal.

**Personality:** Thoughtful, respectful, meticulous, independent, reassuring.

**Voice principles:**

- Treat remembering as an act of care, not relationship optimization.
- Treat users as the authority on what matters in their own lives.
- Be candid about what exists today and what is still planned.
- Explain privacy through product behavior and user control, not vague assurances.
- Use concrete human examples: a friend going through a hard time, a new child, a long-overdue call.
- Avoid growth-hacking language, performative enthusiasm, and feature-list overload.
- Preserve the founder-led intimacy visible in “Write to Regis” and “I read every message” while it remains operationally true.

## Messaging Pillars

| Theme | Core message | Supporting product evidence |
|---|---|---|
| Remember what matters | Keep the human context that contact cards and memory lose | People records, activities, journal, reminders, relationships, timelines |
| Structured your way | Monica presumes as little as possible about what you want to record | Templates, pages, reusable sections, typed fields, value lists, custom entities |
| One life, connected | Documenting a day also documents the people in it | The shared activity model behind the journal and person histories |
| Change without chaos | Personalization never means silent breakage | Frozen published versions, explicit upgrades, snapshots, history, no silent deletion |
| Your record, under your control | Keep intimate information private, portable, and independent | Open source, free unlimited self-hosting, vaults, export, deletion, private shares |
| Open to your tools | Automate or connect without accepting an imposed assistant | Product-wide API intent, official MCP, webhooks, scoped tokens, module system |
| Accessible by construction | User-created structures should remain usable by everyone | WCAG 2.2 AA as a delivery condition and accessible field/builder contracts |

## Proof Points

**Verified repository facts:**

- Monica has been developed by its founder since 2017, according to current in-product founder copy.
- The project is open source and the package declares the MIT license.
- The specification supports the same product in hosted and self-hosted modes; self-hosted accounts are unmetered.
- The product specification commits to no hosted subscription: a limited free offer plus a one-time purchase for full access.
- The product targets WCAG 2.2 AA across the interface, including user-generated structures.
- The codebase includes localization files for English, French, German, Dutch, Spanish, and Portuguese; the product direction includes right-to-left support, but current RTL delivery is not verified.
- Current code and tests cover account/security foundations, vaults, contacts, value lists, entity types, and versioned template-building flows.

**Metrics:** No adoption, retention, usage, performance, accessibility audit, installation, revenue, or customer-outcome metrics were found in the supplied repository. Do not manufacture them.

**Customers:** No approved customer names or logos were found.

**Testimonials:** No verified customer testimonials were found. Founder narrative must not be presented as a testimonial.

**Claims requiring validation before publication:** Current hosted price and entitlements; active user or self-hosting counts; import success; time saved; reminder or relationship outcomes; WCAG audit status; API/MCP availability; marketplace inventory; mobile availability; and customer satisfaction.

## Goals

**Primary business goal:** Make Monica v3 a sustainable, trusted open-source product that people adopt as the long-term home for their personal relationship and life records, with hosted one-time purchases funding continued development. This is inferred from the specification and requires founder confirmation.

**Primary conversion action:** Create an account on the hosted service. For technically inclined visitors, the parallel conversion is to self-host Monica.

**Activation action:** Create the first vault, add the first person, and record the first meaningful piece of context. Once the journal ships, recording a first activity that appears in both the journal and a person’s history should become a key “aha” moment.

**Current metrics:** Unknown. At minimum, consider measuring landing-page-to-registration conversion, registration-to-first-vault activation, time to first person, time to first meaningful field value, first-week return rate, journal/activity adoption once available, export usage, self-host installs, and hosted one-time-purchase conversion.

## Open Questions for Validation

- Which audience segment feels the pain most acutely and adopts fastest?
- What exact words do current Monica users use for the problem and the product?
- Is “personal relationship manager,” “personal CRM,” or another category label clearest to prospects?
- Which three alternatives do users actually switch from, and why?
- What are the most common ethical or privacy concerns, and what guidance earns trust?
- What is the final hosted free allowance, one-time price, and purchase entitlement?
- Which v1 capabilities are safe to promise publicly at the time each campaign ships?
- What is the single primary launch conversion: hosted registration, waitlist, self-host installation, or source-code engagement?
- Which proof points—customer stories, years of use, self-host installs, data portability, accessibility audits—can be substantiated?
