# Erica: product specification

> Erica is the code name of Monica v3. This document describes what the product does and why, without going into the technical side. It reads from the most general to the most detailed: the first sections are enough to understand the intent, the ones that follow specify each feature.

---

## 1. In one sentence

Erica is an open source tool for **documenting your life and the lives of the people around you**, and one where **anybody can redefine the structure** so that it matches their own way of keeping track of people.

---

## 2. The product bet

### 2.1. A tool that presumes nothing

The first two versions of Monica started from an assumption: that you can guess in advance what people want to record about the people close to them (birthday, partner, children, last call, and so on). That assumption is false. Everybody keeps track of different things, and nobody uses more than a third of what is offered to them.

Erica changes stance. Rather than aiming for simplicity through constraint, it aims for **total personalisation**:

- a minimal skeleton is supplied by default, so the tool is usable from the first minute;
- on top of that skeleton, everybody builds their own data structures;
- those structures can be **shared with other users**, which saves everybody from reinventing the same blocks on their own.

The consequence: Erica does not try to be right about what should be recorded. It tries never to be an obstacle to what somebody wants to record.

### 2.2. Two faces of the same product

Erica has two ways in, and they are not separable.

**The first is the directory.** Records about people (and about whatever you want to track around them), with their relations, their important dates, everything you do not want to forget. That is the subject of sections 4 to 8.

**The second is the journal.** What I do, day after day. That is the subject of section 9.

The link between the two is the heart of the product: **documenting your day documents the people who appear in it.** Noting a lunch with somebody fills in both my journal for the day and that person's history. Neither face asks you to enter the same thing twice, and that is precisely what makes the daily effort sustainable.

---

## 3. The principles that guide every decision

### 3.1. Nothing changes under anybody's feet

This is the most structuring principle in the product. No change made by another person, or by the system, may change what somebody sees without them having explicitly accepted it.

Concretely:

- a record stays on the structure it was assigned until somebody decides to move it on;
- a shared structure you installed never updates itself: you are told, you read what changes, you accept or you stay on your version;
- changing a time zone, a preference or a default setting never retroactively alters what has already been fixed.

### 3.2. The core stays stable

Seven objects are never personalisable in their nature: the **Account**, the **Vault**, the **Contact**, the **Activity**, the **Relationship**, the **Group** and the special identity **Me**. Two others complete them (**Reminders** and **Changelog**), not because they are indispensable to every use, but because the product has to be able to handle them transversally, whatever structure was chosen.

Note the nuance: **it is the concept that is fixed, not its content.** An activity will always be something that happened on a date with people, but the list of activity natures and the fields they carry are entirely configurable (see 9.3). The same logic applies to the contact, where only the name is imposed.

Erica is a **mono-user** product. An account corresponds to a single person who signs in. There are therefore no invitations, no roles, no permissions between users, and no shared vault. This simplicity is deliberate: multi-user was little used and added disproportionate complexity to access, reminders, the journal and files.

Everything else is open.

### 3.3. No silent deletion

When something disappears (a field removed from a structure, a deleted contact, a reminder source that no longer exists), the product never makes the associated data disappear silently. It deactivates, it archives, it explains why, and it allows going back.

### 3.4. One tool, two deployment modes

Erica works identically self hosted (free, unlimited) and in the version we host. On the hosted version, a limited free offer coexists with full access, which is held either by a purchase made once, for good, or by a subscription, billed monthly or yearly, that renews until it is cancelled. The two unlock exactly the same product: there are no tiers, no per offer features and no trial, so the only thing a payment changes is whether the free caps apply.

The free offer caps how many vaults and contacts an account may hold and how much storage it may use. An account that finds itself above a cap, after a refund or a subscription that lapsed, keeps everything it holds and can read and edit all of it. Only adding more is refused, and the refusal names the cap and where to lift it.

Whether an instance meters its accounts at all is decided by the instance, not stamped on each account. A self hosted instance meters nobody and shows no offer, no price and no prompt, and a self hoster cannot turn a paywall on for their own users.

### 3.5. Accessible to everybody, without exception

Accessibility is not an option added at the end of a project, it is a delivery condition. The target is **WCAG 2.2 AA** across the whole product, not only on the main screens.

Concretely, that means: everything is usable with the keyboard alone, everything is announced correctly by a screen reader, no information is carried by colour alone, contrasts are respected, focus is always visible, text stays readable zoomed to 200%, and animations switch off if the system asks for it.

This principle has a particular consequence here. Because records are **generated from structures created by users**, you cannot make one screen accessible by hand and move on to the next. Each field type has to produce an accessible rendering by construction, once and for all, otherwise every template a user creates becomes a new source of problems that nobody can fix on their behalf. That applies to the building interface itself: creating a template, navigating its pages and moving sections between them have to be doable with the keyboard and with a screen reader.

### 3.6. Everything goes through the API and can be exposed by MCP

**Everything that can be done in the interface must be doable through the API.** Not just reading and writing contacts: creating a template, adding and ordering pages, moving sections between pages, changing a value list, publishing to the marketplace, installing a structure, accepting an update, creating a relationship, managing reminders, uploading a file and consulting the changelog.

What this brings:

- the mobile applications (3.8) consume the same API;
- automation becomes possible without waiting for us: importing data, syncing with other tools, personal scripts;
- the API acts as a permanent test of the product's completeness;
- an **official MCP server** can expose Erica's capabilities to compatible assistants and tools, without building an artificial intelligence assistant into the product itself.

Erica therefore imposes no built in artificial intelligence. The user stays free to plug in the tool of their choice through MCP, the API or webhooks.

The API is publicly documented, versioned, authenticated by tokens with limitable scopes, and subject to rate limits on the hosted version. The MCP server applies the same authorisation rules and never bypasses the API.

The API and MCP cover the inbound direction, where an external tool comes to query or modify Erica. The outbound direction, where Erica tells an external tool that something just happened, is handled by **webhooks** (section 14).

### 3.7. Multilingual from day one

No text is hardcoded in a single language. That covers the obvious (the interface, emails, notifications) but also what is often forgotten: date and number formats, currencies, plurals, the order of given name and family name which varies by culture, and support for right to left languages.

The least obvious point concerns content created by users. A template shared on the marketplace is written in **one** language by its author. When somebody installs it in another language, the labels stay as they are. Three decisions follow: the marketplace displays the language of each shared item and allows filtering on it, the installer can freely rename what they installed (it is a copy, so it costs nothing), and the content supplied by default with the product is translated into all our languages.

### 3.8. A responsive web interface, then native mobile applications

The first version is a **responsive** web application, usable without major compromise on desktop, tablet and phone. Screens have to genuinely adapt to different sizes: navigation, information density, forms, tables, records, timelines and the template builder. Mobile web is not a forgotten version of the desktop interface.

Eventually, the goal remains an **iOS application written in Swift** and an **Android application written in Kotlin**, with the interface conventions, gestures and performance of each platform.

They come after the first web version, but they shape choices now: the API has to be complete (3.6), push notifications have to exist on the server side, and quick capture on mobile (adding a note about somebody in ten seconds, right after seeing them) has to be treated as a use case in its own right, not as a reduced version of the web screen.

### 3.9. A core extensible by modules

Erica has to let the community develop **modules** without modifying the core of the product. That matters particularly for self hosted instances, which need to adapt Erica to specialised needs without waiting for a feature to be accepted into the official product.

A module can add capabilities around the extension points explicitly provided: screens, commands, events, integrations, field types, imports, exports, automations or connectors. It must not bypass the fundamental rules of the product, in particular the API, accessibility, data compartmentalisation, history and update compatibility.

The module system has to stay distinct from the marketplace of templates and sections:

- a **template** configures data using existing capabilities;
- a **module** adds behaviour or new capabilities to the product.

The version hosted by Monica runs only modules explicitly approved and operated by us. A self hosted instance can install the modules of its choice and takes on the security, maintenance and compatibility itself.

The exact contract of the module system, meaning the format, the lifecycle, the technical permissions, the compatibility and the distribution mechanism, remains to be specified before it is implemented.

## 4. The vocabulary of the product

Ten words to know before reading on.

| Term | What it is |
|---|---|
| **Account** | The personal space for configuration and, on the hosted version, billing. An account corresponds to a single user. |
| **Vault** | A watertight compartment of data, for example "Family", "Work" or "Friends". Contacts, entities and activities live in a vault. |
| **Contact** | A person being documented. |
| **Me** | The special contact representing the user themselves. |
| **Relationship** | A structured human link between two contacts, read in both directions. |
| **Group** | A set of contacts that exists independently of their relationships: household, couple, social circle, team, family. |
| **Entity** | A type of object invented by the user (Animal, Company, Recipe, Musical instrument, and so on) that lives alongside contacts. |
| **Activity** | Something that happened, on a date, possibly with contacts or entities. It is the building block of the journal. |
| **Journal** | The day by day reading of everything you lived through. The counterpart of the record, which is the reading by person. |
| **Private share** | A revocable link giving limited access to a contact record or a vault, without creating an account or collaboration inside Erica. |

### 4.1. One account, one person

Erica is deliberately **mono-user**. An account belongs to a single person and contains only one sign in identity.

There are no account roles, invitations, members, permissions between users or real time collaboration. This decision removes a layer of complexity that was little used in previous versions and makes the rules for privacy, notification, history and storage far more predictable.

### 4.2. Vaults as personal compartments

A vault remains a watertight compartment allowing contexts such as "Family", "Work" and "Friends" to be kept apart. It serves to organise and compartmentalise data, not to manage permissions between several users.

A contact or an activity belongs to a specific vault. Searches, exports, webhooks, modules and private shares can be limited to a vault. Moving data from one vault to another is an explicit and recorded action.

### 4.3. The notion of Me

Every account has a special contact called **Me**, representing the person using Erica.

Me is a real contact: it can carry a template, custom fields, activities and relationships, and it can belong to groups. This identity makes it possible to model sentences like "Alice is my sister", "I had lunch with Marc" or "we lived in Montreal" correctly, without creating an invisible parallel logic.

Me does, however, have invariants:

- it is unique within the account;
- it cannot be deleted or merged into another contact;
- it is stably identifiable by the API, MCP, imports and modules;
- its name and its record stay editable like those of a normal contact;
- relationships displayed from Me read naturally from the user's point of view.

When importing old data, Erica has to allow identifying which existing contact represents Me, or creating one.

### 4.4. Sharing without reintroducing multi-user

Erica allows sharing a **contact record** or a **vault** by means of a private link, the way you share a document.

That share gives read access, not collaborative access to the application. The recipient does not need an Erica account and never becomes a user of the source account.

Every share link is:

- revocable at any time;
- limited to a specific record or vault;
- protected by an unguessable token;
- optionally given an expiry date and a password;
- configurable to include or exclude files, activities, relationships, sensitive fields and other sections;
- journalled, with the date of creation, the last consultation and the revocation.

By default, sensitive fields, the personal narrative of the journal, the modification history and technical data are never exposed.

Sharing a vault stays a read only view. It recreates neither fine grained permissions, nor multi person editing, nor shared notifications, nor collective ownership of the data.

## 5. Personalisation: the heart of the product

### 5.1. Two different needs

**First need: adding information to something that already exists.**
I want to record my contacts' shoe size, or their diet, or their children's allergy. The contact stays a contact, it simply carries data Monica had not planned for.

**Second need: inventing an object that does not exist.**
I want to keep track of my friends' animals, the companies I work with, or the bottles in my cellar. None of those objects is a contact, and none of them existed in the product.

Both needs go through the same mechanism, which means there is only one thing to learn.

### 5.2. The mechanism: Template, Pages, Sections, Fields

Four levels, from the broadest to the finest:

- **The Template** is the complete definition of a record. It is what you apply to a contact, an entity or an activity, and it decides what appears on the record.
- **The Page** is a named and ordered part of a template. Every template version contains at least one page. A page belongs to that template version and is not reusable or publishable on its own.
- **The Section** is a reusable thematic block, for example "Address", "Social networks", "Medical follow up". Each section in a template version is placed on exactly one page, in whatever order the user wants.
- **The Field** is the elementary piece of information inside a section: "Street", "City", "Postcode". Each field has a type (text, date, list of choices, file, and so on) and its own constraints.

In this section, **page** means a page inside a template. **Record view** means the complete contact, entity or activity screen that contains the header and every template page.

The page exists to make a long record understandable. The section stays separate because sharing an isolated block ("here is my Medical follow up section") must remain possible without imposing the template or page around it.

Pages are part of the frozen template version. Renaming, reordering, adding or removing a page is a structure change and therefore happens only in a draft. Moving a section to another page is also a structure change. Pages carry presentation and navigation only. Values still belong to fields, and search, filtering, reminders and exports work across every page without requiring the user to select one.

The record interface follows these rules:

- The identity header, primary actions and highlighted fields sit above the page navigation and remain visible whichever page is selected. Highlighted fields may come from any page.
- A template with one page renders its sections directly and does not show a redundant tab list. A template with two or more pages renders one tab per page, in template order.
- The first page is selected when the record opens, unless the URL identifies another page. The selected page is represented in the URL by its stable key, so a page can be linked to, refreshed and traversed with the browser history.
- Tabs use the accessible tab pattern. The tab list, selected tab and tab panels have programmatic relationships. Arrow keys move between tabs, focus stays visible, and changing tabs is announced without moving focus into the panel unexpectedly.
- On narrow screens, the tab list scrolls horizontally and keeps the selected tab visible. Tabs do not wrap into several rows, and the record does not collapse all pages into one long mobile page.
- When saving reveals an error on another page, the interface selects that page, focuses the first invalid field and announces the error summary. Required fields apply across the whole record, not only to the selected page.

The template builder always shows the page structure, including when there is only one page. It lets the user add, rename and reorder pages, move sections between them and reorder sections inside a page. The last page cannot be removed. A page containing sections must be emptied by moving or removing those sections before it can be removed. Every destructive control names the page and explains the consequence before acting.

### 5.3. Custom entities

Inventing a type of object happens in two steps: you declare the type ("Animal"), then you associate one or more possible structures with it. The same type can have several templates, and the template applied by default can vary from one vault to another. The "Family" vault and the "Professional breeding" vault can both handle "Animals" at a completely different level of detail.

Once created, an entity never changes type. Its display structure, on the other hand, can be changed at any time.

### 5.4. Shared value lists

Many fields offer a choice among values: genders, countries, languages, currencies, phone labels (mobile, home, work), contact categories (family, close friend, colleague). Rather than retyping those lists in every field, they are defined once at the account level and reused everywhere.

Erica supplies a set of ready to use lists when the account is created. Each one is editable: you add your own values, and you can deactivate a value supplied by default without deleting it, so as not to break the records already using it.

### 5.5. The available field types

Without going into detail, here are the families offered.

- **Simple**: short text, long text, number, yes or no, web address, email, phone.
- **Choice**: dropdown, multiple choice, radio buttons, free tags.
- **Dates**: precise date, recurring date (a birthday, whose year may be unknown), duration ("together since...").
- **Relations**: link to another contact or another entity, optionally qualified ("owner of", "vet of").
- **Files**: file, image, avatar.
- **Composite**: full address, amount with currency, full name, and the **repeater**, which allows entering the same block several times (several phone numbers, several addresses).
- **Miscellaneous**: rating out of 5 stars, rich text, colour, geographic position.
- **Layout**: divider, sub-part heading, help text. These elements store no data, they exist purely to make a long record readable.
- **Calculated** (planned for later): a value derived from other fields, such as age from a date of birth, or a count ("number of animals this contact has").

### 5.6. Two cross-cutting settings on any field

- **Sensitive field**: the value is hidden by default and revealed on click. It is protection against a glance over the shoulder, not an authorisation mechanism. Private links exclude it by default, but the account owner can always reveal it.
- **Highlighted field**: the field appears in the upper area of the record, with a rendering suited to its type (a pill for tags, a countdown for a recurring date).

### 5.7. What stays imposed on a contact

The **name** stays structured by the product, in all its components: title, given name, middle name, family name, suffix, maiden name, nickname. The reason is simple: the product itself uses it constantly to sort, to search, to display headers and to generate initials. It cannot depend on a field the user might not have created.

Conversely, information you would think fundamental is in fact made of customisable fields supplied by default: gender, date of birth, description. They are there from the start, but they can be removed, renamed or replaced.

---

## 6. Versioning: how chaos is avoided

Personalising to this extent creates an obvious risk: if everybody can modify structures, records become unstable. The product's answer comes down to two rules.

**First rule: a published structure is frozen.** Once a version of a template or a section is published, it never moves again. Any modification creates a new version alongside it, with its own change note. Pages, page order and the placement of sections on pages are part of that frozen template version. The rule applies in cascade, including to value lists: renaming an option elsewhere in the product cannot change the behaviour of a section published yesterday.

As long as a structure is a draft, it stays freely editable. Freezing happens at publication, so as not to create a version at every micro-adjustment during design.

**Second rule: a record points at a precise version.** A contact never follows "the latest version". It follows the version it was assigned, until somebody decides to change it. That change is a deliberate action, and it automatically triggers a save of the previous state, which is restorable.

---

## 7. The marketplace

This is the natural consequence of personalisation: if everybody builds their own structures, they may as well be able to exchange them.

The author's path:

1. They create a template or a section. It is **private** by default, visible to them alone.
2. When they want to share it, they choose a category from a list we maintain (Health, Finance, Animals, and so on). There are no free keywords, to avoid the catalogue becoming unreadable.
3. They submit, we **moderate**, and the content becomes public once validated. A refusal is explained and communicated.

The installer's path:

1. They browse the catalogue by category and install what interests them.
2. **Installing means copying.** They get their own private copy, which they can modify freely without affecting the original or depending on its author.
3. That copy keeps a trace of its origin. If the author publishes a new version, the installer receives a **notification with the change note**, and chooses whether to apply the update or stay on their version.

Two design points to note: you can publish a **section on its own**, without having to build a complete template around it, which lowers the barrier to contributing considerably. And there is **never any forced synchronisation**, which is the direct application of the principle in section 3.1.

The number of installations of an item is displayed publicly, as a quality signal.

---

## 8. Relationships between people

### 8.1. Why this is a system of its own

We could have treated "parent of", "spouse of" and "friend of" as one customisable field among others. That would lose the direction of reading, the history of a relationship and the stable meaning required to draw a family tree. Relationships therefore have their own small model.

Erica therefore makes this a capability of its own belonging to contacts, deliberately **not extended** to custom entities. The link between a contact and their dog stays an ordinary relation field.

### 8.2. Categories and types

**Categories organise the vocabulary.** Every account starts with Family, Romantic, Friendship, Professional and Other. The user may create, rename, reorder and deactivate categories. Supplied categories cannot be deleted. A category is only a display group and never gives a relationship product behaviour.

**Types describe the relationship.** A type belongs to one category and defines the words read from both sides. Friend is symmetric. Parent / Child is directed: A is the parent and B is the child. The user may create, rename, reorder, move and deactivate types. Supplied types cannot be deleted.

The supplied types are Parent / Child, Sibling, Grandparent / Grandchild, Aunt or Uncle / Niece or Nephew, Cousin, Partner, Fiancé, Spouse, Friend and Colleague.

**A small protected behaviour survives display changes.** Parent / Child is the only supplied type that feeds parentage in the family tree. Partner, Fiancé and Spouse feed romantic history. Renaming or moving these types changes no behaviour. A custom type named Parent or moved into Family remains an ordinary type.

**A relationship is read in both directions.** It is entered once and stored once. Several types may link the same two people: two siblings may also be colleagues.

**A large relationship list remains usable.** On a contact record, relationships can be filtered by category, type, current or ended status, and the role read from that contact's side. Parents and Children are direct filters backed by the protected Parent / Child behaviour and its direction, not by a displayed name. Filtering, counts, sorting and pagination happen in the database, so requesting the children of a contact does not load their other relationships first.

**Time stays simple in the first version.** A relationship is current or ended and may carry a start and an end date. An ended relationship does not require a known end date. A renewed relationship is recorded as another row. More precise and repeated periods can be added later without changing the meaning of these rows.

**The household is not the couple.** Two partners can live apart, have several households, remain co-parents after a breakup. The product therefore models **groups** (household, family unit, couple, social circle, team) separately from relationships, with their own dates of entry and exit. A household is never automatically deduced from a couple.

**Mentioned people become real contacts.** When you enter "Monica's son, Jack", Jack becomes a contact in his own right, but a **partial** one: reduced to a first name and a relationship, hidden from the main lists while he stays that way. You enrich him later, or merge him with an existing contact. There is never a parallel free text describing a human without them existing in the graph.

**Groups are distinct objects.** A household, family, team or social circle has its own name and members. It is never created or suggested from a relationship in the first version.

### 8.3. Explicit projections and safeguards

- **The family tree is a day-one feature containing only recorded facts.** Starting from any contact, it follows non-deleted Parent / Child rows recursively to show recorded ancestors and descendants, and may show recorded romantic links beside them. Its branches are loaded as needed rather than by loading every relationship in the vault. Parentage remains in the tree regardless of temporal status: death or estrangement does not erase it. Following a chain may place a grandparent, grandchild or sibling in the tree; it does not create or assert a Grandparent, Sibling, Aunt, Uncle or Cousin relationship.
- **Romantic history contains only recorded facts.** It orders explicit Partner, Fiancé and Spouse rows using their status and known dates. An ended row is the history of an ex relationship; Ex-partner is not a supplied type.
- **No deduction or suggestion runs in the first version.** The product never guesses a link from names, shared parents, dates, households or other relationships.
- **Invalid structures are refused.** A contact cannot link to itself, a relationship cannot cross a vault, Parent / Child cannot form a cycle, and an end date cannot precede a start date. Moving one contact is refused when it would make an existing relationship cross a vault. Checking a cycle validates the row being entered and does not infer another relationship.
- **History remains visible.** Creating, editing, ending, deleting and restoring a relationship writes to the changelog and may appear on the contacts' timelines.
- **Merging contacts preserves every explicit relationship.** The preview shows any exact duplicate created by the merge and the user decides what to keep. The merge does not combine different types or histories by guessing that they mean the same thing.

### 8.4. Later extensions

The model may later gain reusable labels, qualifiers such as biological or adoptive, imprecise dates, several periods on one relationship and user-recorded certainty.

A later version may also calculate or suggest relationships from the explicit graph. Such results must live separately from confirmed relationships, retain the recorded links that produced them and never become facts without an explicit user action. None of that engine, storage or interface belongs to the first version.

---

## 9. The journal and activities

### 9.1. The second facet of the product

Everything above describes a way of documenting **other people**. Half of it is missing: documenting **your own life**, day after day, the way you would keep a personal diary.

These two halves are not two products placed side by side. It is the same gesture seen from two sides. When I note that I had lunch with Pam on Tuesday, I fill in my journal for Tuesday **and** I fill in Pam's history. One entry, two readings.

That is what makes the journal sustainable over time. A classic diary demands a daily effort that returns nothing in the short term, so it gets abandoned after three weeks. Here, every line written immediately enriches something else: the record of the people involved, inactivity reminders, search, statistics. You are no longer writing for a hypothetical future self, you are writing for a tool that uses it straight away.

### 9.2. The activity

The activity is the basic unit of both facets. It is a fact: something happened, on a date, possibly with people.

An activity always carries:

- **a date**, or a period when it lasts several days (a trip, a hospital stay);
- **a nature**: lunch, call, concert, medical appointment, argument, walk, and so on;
- **participants**, contacts or entities of the vault. There may be none at all, a day can be documented without anybody else being involved;
- **a free narrative**, plus the fields specific to its nature;
- **files**: photos, tickets, scans.

**The concept of an activity is part of the core and is never personalisable in its nature.** The reason is the same as for the contact: the product uses it transversally, to build a timeline, compute inactivity, feed search and statistics. If the activity were merely one user invented object among others, none of those features could work reliably.

**The nature of activities, on the other hand, is entirely configurable.** That is where personalisation comes back.

### 9.3. Activity natures

The list of natures is configured at the account level, like the other shared value lists (see 5.4). Erica supplies a starting set that is immediately usable, and everybody adapts it: adding to it, renaming, deactivating without breaking the past activities that use it.

Each nature carries:

- **a label, an icon and a colour**, so the journal reads at a glance;
- **whether or not it counts as a real interaction** with the participants. A lunch counts, a call counts, "I thought about him" or "I saw a photo of her" do not. That setting, and it alone, feeds inactivity reminders (see 10.2);
- **its own field structure**, defined with the same engine as contact records: sections and fields (see 5.2). A "Restaurant" nature can carry the venue, the dish and a rating out of 5. A "Medical appointment" nature can carry the practitioner, the reason and a scanned prescription. A "Call" nature may carry nothing but a duration.

Direct consequence: **an activity nature can be shared on the marketplace** like any other structure, with the same versioning and installation rules (sections 6 and 7). Somebody who has designed a good log for mountain outings, or a good log for medical appointments, can publish it as is.

### 9.4. The journal, the reading by day

The journal is the chronological view of all of this. A day can contain:

- the **activities** of the day;
- a **narrative of the day itself**, which belongs to no particular activity;
- a **general mood**, chosen from a list that is itself configurable.

A day can have nothing but a mood. An empty day stays an empty day, the product makes nobody feel guilty and asks for nothing.

**Two ways to enter the same information**, because both correspond to real moments:

- **by the day**: in the evening, I tell the story of my day and hook the people concerned into the text;
- **by the person**: I have just seen somebody, I add the activity from their record, and it files itself into the journal for the right day.

Both produce exactly the same thing. This is the main scenario for quick capture on mobile (see 3.8): recording a meeting in ten seconds, in the street, straight afterwards.

You can of course fill in a past day, or reconstruct entire years after the fact. As with relationships (see 8.2), a date can stay imprecise, and the product never forces a precision you do not have.

### 9.5. What activities feed elsewhere

This is the whole reason the mechanism exists. Activities are not an isolated module.

- **A contact's timeline.** On somebody's record, activities mix with relationship events (see 8.3) to form a single readable story.
- **Inactivity reminders** (see 10.2). "Tell me if I have had no contact with this person for 90 days" only makes sense if there is a trace of that contact. Without activities, it is a reminder that can measure nothing.
- **Event relative reminders** (see 10.2), of the kind "14 days after the last lunch".
- **Statistics**: who I see the most, who I neglect, how it changes over time, the breakdown by activity nature.
- **Search** (see 13), which has to find an activity by its content the way it finds a contact.
- **Photos**, which finally get a natural place to be filed: the day they were taken, with the people who were there.

### 9.6. The privacy of the journal

The journal is personal. In Erica, an activity, its narrative and the day it belongs to are private by default because the account has only one user.

When a contact record or a vault is exposed by a private link, activities and narratives are never included implicitly. The owner explicitly chooses what may be visible:

- the structured facts of an activity;
- its free narrative;
- the associated files;
- the overall narrative of a day.

The setting can be defined at the link level and then tightened activity by activity. A field marked as sensitive stays excluded by default, even if the activity is shared.

## 10. Reminders

### 10.1. The problem to solve

A personal CRM that reminds you of nothing is useless. But "reminder" covers four different things that have to be distinguished for the product to behave correctly:

- **the source**: where the need for a reminder comes from (a date entered, an inactivity, a task, a manual entry);
- **the rule**: the computation logic ("every year, 7 days before");
- **the occurrence**: a concrete deadline ("5 May 2027 at 9am");
- **the notification**: what actually reaches the user, on a given channel.

Without that distinction, you can neither postpone a reminder cleanly, nor keep the history of what was done with it, nor correctly deliver several notifications for the same deadline.

**Each offset of each cycle produces its own occurrence.** A yearly rule reading 12 May with the offsets "7 days before" and "on the day" produces two occurrences in 2027: 5 May at the chosen local time and 12 May at that time. Each occurrence can then produce one notification on every enabled channel. The offset is applied when the occurrence is generated and is never applied again by the notification. Postponing or dismissing the 5 May occurrence touches neither the 12 May occurrence nor either occurrence of the following year.

### 10.2. The types of reminders

- **One off**: a date, once.
- **Recurring**: every year on 12 May, every Monday and Friday, and so on.
- **Field based**: the product reads any `date` or `recurring_date` entered on a record and derives deadlines from it, with one or more offsets ("7 days before" and "on the day"). The source provides the record, field and value; the reminder separately defines whether it fires once or recurs, and at which frequency. A complete `date` can therefore drive a recurring reminder. A `recurring_date` is useful when the calendar date itself repeats or its year is unknown, and may suggest a yearly cadence without imposing it. Saving a date never creates a rule silently: the interface offers to add one, prefilled from that context, and the user chooses its wording, cadence, offsets and local time.
- **Relative to an activity**: "14 days after the last lunch". It relies directly on the journal (see 9).
- **By inactivity**: "tell me if I have had no contact with this person for 90 days". This is probably the most important reminder in a personal CRM, and the most absent from existing tools. It is computed from activities marked as counting as a real interaction (see 9.3), which means its quality depends entirely on the journal being kept.

### 10.3. Expected behaviours

**Postponing and dismissing are the two actions on an occurrence.**

- *Postponing* means "not now, remind me at this new date". It moves only this deadline to an exact future instant, keeps its original instant as history, and abandons every delivery still waiting for the old one. It may be done more than once and never touches a sibling or a later cycle.
- *Dismissing* means "do not interrupt me about this deadline again". It closes this occurrence, abandons every delivery that has not gone out, and makes no claim that an action was completed. Notifications already sent remain as history. Dismissing this year's birthday does not remove next year's.
- *Deactivating the rule* is the only action that cancels every future deadline, and it is reversible.

**The time is local, not absolute.** "Every year at 9am" means 9am local time, including after a daylight saving change. The reference zone is frozen when the rule is created, and changing your personal zone later never shifts a reminder that is already set.

**Changes to the source break nothing.** If a date is corrected, future deadlines are recomputed and the history is preserved. If a contact is deleted, future deadlines are cancelled but everything comes back if it is restored. If the field feeding the reminder disappears from a structure, the rule is deactivated and the product **explains why it is inactive** instead of making it disappear.

### 10.4. How the user is notified

How you get notified is configured at the account level:

- the channels enabled (in app, email, push notification);
- the **quiet hours**, during which nothing may disturb the user. A reminder due at 3am can be delivered at 8am;
- the **reception mode**: immediate, or grouped into a daily digest;
- the exceptions defined on a specific rule.

A reminder always belongs to a single account and is never sent to people viewing a private share link.

### 10.5. A reminder and a task are not the same thing

A **reminder** is a temporal interruption: "Rachel's birthday is in 7 days". A **task** is an action to carry out: "buy a present for Rachel", with a deadline, a status, an owner and a completion date.

A task can carry several reminders, a reminder can exist without a task. The product does not merge the two notions, but the task will rely on the same scheduling and notification machinery rather than having a second one in parallel.

---

## 11. Files and attachments

Files (photos, documents, scans) are attached to records through dedicated fields. The product knows at all times which files are actually in use and which are no longer, which allows two things: displaying a reliable **storage quota** on hosted accounts, and **automatically deleting orphaned files** after a grace period.

That grace period is not cosmetic: restoring an old version of a record has to be able to recover a file detached recently.

Storage relies on the **provider configured in Laravel**, without assuming that a type of deployment necessarily uses a local disk or a specific cloud service. A self hosted instance can therefore use a local disk, S3 compatible storage or any other supported driver. The hosted version uses the infrastructure chosen by Monica.

Whatever the infrastructure, files are organised and isolated at minimum by **account**, then by **vault**. No public path, file name or predictable identifier may allow that separation to be crossed. Downloads, previews, asynchronous processing, exports, modules and private links must all apply the same compartmentalisation.

The product behaviour stays identical whatever the provider: attaching, detaching, restoring, computing the quota, detecting orphaned files and deleting after the grace period.

## 12. History and traceability

Two complementary mechanisms, with two distinct uses.

**Record snapshots** capture the complete state of a contact or an entity at a given moment, notably before a structure change. They exist to **go back**.

**The changelog** answers a different question: who modified what, when, from which value, and from which interface (site, application, import, automation). It covers every object, not only records: vaults, structures, private links, modules and settings. It is never modified or deleted, except under an explicit retention policy.

*Still open: a field marked as sensitive is hidden from display, but its old value would appear in clear text in the changelog. Should sensitive values be masked in the changelog, or should access to the changelog itself be restricted?*

---

## 13. Search

Search has to work on **all** fields, including ones the user invented yesterday, with no configuration on their part. Two uses are covered:

- **free search**, tolerant of typing mistakes, across the whole content of a record;
- **structured filtering** on any custom field, for example "every contact whose city is Paris and whose diet is vegetarian".

Indexing is asynchronous: saving a record stays instantaneous, and the search index updates right behind it in the background.

Search inside the marketplace is a distinct need, at a smaller scale, and will be handled separately.

---

## 14. Webhooks

### 14.1. Why

The API lets you come and ask Erica what is going on. It does not let you be told. Without webhooks, any tool wanting to react to an event is condemned to poll the API in a loop, which is slow, expensive, and in any case impossible for a self hosted instance behind a home connection.

Webhooks are therefore the outbound counterpart of principle 3.6: **if an action exists, it must be listenable from outside.**

The intended uses:

- plugging Erica into a personal automation tool to trigger any reaction whatsoever;
- syncing in one direction or the other with a calendar, a task list, a notes tool;
- feeding a dashboard or an external backup without exporting the whole account each time;
- allowing somebody to build their own notification, on a channel we will never provide.

### 14.2. What can be listened to

The list of events follows the product, not a sub-part of the product. At minimum you can listen to:

- **Contacts and entities**: creation, modification, deletion, restoration, merge, change of applied structure.
- **Activities and journal**: an activity is created, modified or deleted, a day is filled in. This is probably the most useful event to listen to, since it allows rebroadcasting your life to any other tool.
- **Relationships**: creation, modification, status change, deletion and restoration.
- **Reminders**: a deadline arrives, it is postponed or dismissed, a rule is deactivated.
- **Structures**: a template or a section is created or published, a new version is published.
- **Marketplace**: an item is installed, an update is available, an update is accepted.
- **Files**: a file is added, detached, deleted.
- **Account and vaults**: a vault is created, renamed, moved or deleted; a private link is created or revoked; a setting changes.

Every send contains the event type, the moment, the object concerned, what changed, and who is behind it (a person, or the system).

### 14.3. How you subscribe

A subscription is created from the settings, or through the API like everything else. It carries:

- a **destination address**;
- a **selection of events**, never "everything" by default. You choose what you want to receive;
- a **scope**: the whole account, or a specific vault. This matters, because vaults exist to compartmentalise contexts. A subscription limited to a vault never receives events from the others;
- a **secret**, which lets the recipient verify that the call really comes from Erica and has not been tampered with.

A test function allows sending a dummy event to check the wiring before relying on it.

### 14.4. Delivery guarantees

This is the part that decides whether the feature is usable or not.

- **Sending happens in the background.** A slow or broken webhook never slows down saving a record.
- **Spaced retries** on failure, over several hours, before abandoning the event.
- **Every event carries a unique identifier**, which lets the recipient recognise a duplicate if a retry arrives twice. You have to assume an event can arrive more than once.
- **A consultable send log**: which event, when, what response, how many attempts. It is the only way for somebody to diagnose their integration without writing to us.
- **Automatic deactivation after repeated failures**, with a notification to the user explaining why and a button to switch it back on. A dead endpoint must not generate traffic for months. In line with principle 3.3, we deactivate and explain, we never delete the subscription silently.

### 14.5. Accepted limits

- Webhooks **leave** the application. What goes out is no longer under our control, so the interface says so clearly when a subscription is created, particularly for vaults containing fields marked as sensitive.
- On the hosted version, the number of subscriptions and the volume of sends are capped. Self hosted, there is no limit.
- Webhooks notify, they do not replace the API. An event says what happened and to what; to get the complete state of the object, you call the API back.

*Still open: should an event contain the new value of the modified fields, or only the information that they changed? The first option is far more practical, the second avoids sending sensitive data out to a third party server. One approach is to make it configurable per subscription, with sensitive fields excluded by default.*

---

## 15. Scope of the first version

**In v1**

- The complete core: mono-user account, personal vaults, the Me contact, contacts, groups and read only private share links.
- The complete personalisation engine: templates, pages, sections, fields, versions, shared value lists.
- Custom entities.
- **The journal and activities** (see 9), with configurable natures, their own fields, the day view, the timeline on a contact's record and quick capture. This is half the product, not a side feature: without it, inactivity reminders and statistics have nothing to measure.
- The essential field types: text, long text, number, yes or no, web address, email, phone, dropdown, multiple choice, date, recurring date, relation, file, image, repeater, address, layout elements, as well as marking fields as sensitive.
- The simple system of relationship categories, relationship types and explicit dated relationships, including the family-tree and romantic-history projections described in section 8.
- Duplicate detection and merging, with preview, conflict resolution, relationship transfer and history preservation.
- Reminders and notifications.
- Search.
- The marketplace.
- The complete public API, covering everything above (see 3.6). It is not a separate brick to be added afterwards: it is how the web interface itself works.
- An official MCP server covering the stable capabilities of the API, with no artificial intelligence assistant built into the product.
- Webhooks (see 14), with their send log and their automatic deactivation. The catalogue of events can grow later, but the mechanism ships in v1.
- A responsive web interface on desktop, tablet and phone.
- Accessibility at WCAG 2.2 AA level (see 3.5) and multilingual support (see 3.7), which are delivery criteria for every screen, not independent workstreams.
- The foundations of the module system: stable extension points, manifest, installation cycle and safeguards. The module catalogue can stay limited at launch.

**Deferred to later**

- Native iOS and Android mobile applications (see 3.8). They follow the v1 web release, but the API and the push notifications they require ship in v1.
- Secondary field types: rating out of 5 stars, tags, amount with currency, composite full name, qualified relation, rich text, colour, geographic position.
- Calculated fields and aggregations.
- Tasks as an object in their own right.
- Automatic feeding of the journal: recognising people mentioned in free text, importing from a calendar, matching against the photos on the phone. These are capture accelerators, not prerequisites, and each raises its own privacy questions.
- Advanced statistics on activities. V1 limits itself to what is directly readable on a record and in the journal.
- Personalising objects other than the contact and entities (account, vault). The mechanism is designed for it, but it will not be exposed straight away.
- A complete ecosystem for distributing, rating and automatically updating modules.
- Relationship labels, qualifiers, imprecise or repeated periods, recorded certainty, and every automatic relationship deduction or suggestion described as a possible extension in section 8.4.

## 16. What remains to be decided

- The detailed layout of the builder for creating pages, sections and fields. Its page operations and accessibility contract are settled in section 5.2, but the complete composition of this screen still needs design work. This is the most delicate point in the product: it is where personalisation becomes either a superpower or an unusable machine.
- The rendering of the repeater, which is the most interactive field in the catalogue, and therefore the most exposed on the accessibility front.
- Can a template shared on the marketplace carry several translations of its labels, or does it stay frozen in its author's language (see 3.7)?
- Do the mobile applications work offline, and if so how far (consultation only, or capture with deferred sync)?
- The detail of the relationship interface for custom entities.
- Should there be fresh moderation for every new version published on the marketplace, or trust granted to authors already validated?
- What becomes of a shared value list when a section using it is installed into another account? Do we copy it, or map it to the installer's equivalent list?
- How should sensitive fields be handled in the changelog (see 12)?
- Does a webhook carry the modified values or only the nature of the change (see 14.5)?
- What level of granularity should a private link offer: the whole record, chosen pages or sections, a saved view, or a frozen snapshot?
- Does a private link to a vault always reflect the current data, or can it publish an immutable snapshot?
- How far can a module extend the interface, the data model and the processing without compromising updates, security and accessibility?
- How do we sign, distribute, update, deactivate and diagnose modules?
- What exactly does the **URL rewrite** need mentioned during the review mean: customising public URLs, redirects after a migration, record slugs, or something else? This point remains to be clarified before entering the spec.
- The **admin** part is missing: what do we need, what can we see, what can we manage?
