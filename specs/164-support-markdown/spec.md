# Feature Specification: Markdown in support requests

**Feature Branch**: `2026-11-26-support-markdown`

**Created**: 2026-11-26

**Status**: Draft

**Input**: User description: "je veux qu'un ticket de support (original ou une reponse dans un thread) supporte markdown. je veux que l'affichage supporte markdown egalement, tant dans l'espace admin que coté end user. je veux que la securité soit prise tres au serieux pour eviter toute attaque."

## Context

Today a support message is plain text everywhere. What somebody types is stripped of anything that
looks like an HTML tag when it is saved, and both the support area and the administration space show
it as typed, line breaks kept, so `**urgent**` reads with its asterisks. The emails are the exception:
they already pass the text through a Markdown converter without anybody having decided it, so the
same message can arrive formatted in an inbox and raw on the screen.

A support thread is written by two parties who do not trust each other in the same way. Anybody with
an account can open a request, and what they write is read by an administrator, who holds the
instance. A formatting feature on that path is a way to reach the most privileged person on the
instance, so the safety requirements below are the core of this feature, not a section added to it.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Somebody writes a formatted request and reads it formatted (Priority: P1)

Somebody reporting a problem writes the steps as a numbered list, pastes the error inside a code
block and puts one word in bold. After sending, their request shows the list as a list, the code in a
fixed-width block, and the word in bold.

**Why this priority**: The request as first written carries most of what the support team needs. A
numbered list of steps and a pasted error are what makes a report readable.

**Independent Test**: Open a request whose body holds a list, a code block, a link and bold text, and
read it back on the request screen.

**Acceptance Scenarios**:

1. **Given** the new request form, **When** somebody sends a body containing `1. Open the vault`, `2. Click Save` and a fenced block holding an error message, **Then** the request screen shows an ordered list of two steps and the error in a fixed-width block, exactly as typed, with no character inside the block reformatted.
2. **Given** a body containing `[the guide](https://example.com/guide)`, **When** it is shown, **Then** the words "the guide" are a link to that address, which opens in a new tab and tells the destination nothing about where it came from.
3. **Given** a body containing `<b>hello</b>` outside a code block, **When** it is shown, **Then** the reader sees the characters `<b>hello</b>` as text, and nothing is made bold by them.
4. **Given** a body containing `a < b and c > d`, **When** it is saved and shown, **Then** every character is kept and shown as typed.

---

### User Story 2 - The support team reads and answers in Markdown (Priority: P1)

An administrator opens the request in the administration space and sees it formatted exactly as its
author does. They answer with a short list of things to try and a link to the help centre, and the
person sees that answer formatted in their request and in the email announcing it.

**Why this priority**: The administration space is where an attack would do the most harm and where
the formatting is read most. Both halves of a thread have to render the same way.

**Independent Test**: Send a reply holding a list and a link from the administration space, then read
it in the administration space, in the requester's request screen and in the email they receive.

**Acceptance Scenarios**:

1. **Given** a request written in Markdown, **When** an administrator opens it, **Then** it is shown with the same formatting the author sees.
2. **Given** an administrator's reply containing a list and a link, **When** the requester opens the request, **Then** the reply is formatted there, and the email announcing it carries the text safely, as described in FR-010.
3. **Given** an internal note written in Markdown, **When** an administrator reads the thread, **Then** the note is formatted the same way, and it remains invisible to the requester.
4. **Given** a follow-up message from the requester in the same thread, **When** it is shown on either side, **Then** it follows the same rules as the original request.

---

### User Story 3 - Nothing written in a message can attack its reader (Priority: P1)

Somebody tries to use a message to run code in the browser of the administrator who reads it, to
steal their session, or to make them load something from a third party. None of it works, in the
support area, in the administration space, or in the emails.

**Why this priority**: The user asked for security to be taken very seriously. A stored script in a
message read by an administrator is a takeover of the instance.

**Independent Test**: Format a collection of known hostile payloads and confirm nothing executes,
nothing loads from elsewhere, and every payload comes out as harmless text or a harmless link; then
confirm the two screens and the emails show one of them as harmless text.

**Acceptance Scenarios**:

1. **Given** a body containing a `<script>` element, an `<img onerror=...>`, an `<iframe>`, an `<svg onload=...>`, a `<style>` element or an inline `style` attribute, **When** it is shown anywhere, **Then** none of it is interpreted, and the reader sees the characters as text.
2. **Given** a link whose address starts with `javascript:`, `data:`, `vbscript:` or `file:`, in any spelling (mixed case, encoded characters, leading spaces or control characters), **When** it is shown, **Then** it is not an active link.
3. **Given** an image written in Markdown, `![x](https://tracker.example/pixel.gif)`, **When** the message is opened or the email arrives, **Then** nothing is fetched from that address; the image is shown as a link the reader may choose to follow.
4. **Given** a link whose visible text is one address and whose destination is another, **When** the reader hovers or focuses it, **Then** the real destination is discoverable before they follow it.
5. **Given** a body built to be slow to format (thousands of nested quotes or list levels, or a very long run of emphasis markers), **When** it is saved and shown, **Then** the screen still renders promptly for every reader and the instance is not slowed for anybody else.

---

### User Story 4 - Somebody learns that formatting is available (Priority: P2)

Somebody about to write a request learns that they can format it, and how, without leaving the form.

**Why this priority**: Formatting nobody knows about is formatting nobody uses, but writing in plain
text still works without it.

**Independent Test**: Open each of the four boxes where a message is written and find how formatting
works from there.

**Acceptance Scenarios**:

1. **Given** the new request form, the requester's reply box, the administrator's reply box or the note box, **When** somebody looks at it, **Then** a single line under it says that Markdown is accepted and links to a page explaining the syntax.
2. **Given** any of those boxes, **When** somebody writes, **Then** there is no preview and no formatting toolbar: they see the result once the message is sent.

---

### User Story 5 - The API and the MCP server carry the Markdown as written (Priority: P2)

A program reading a support request through the API, or an assistant reading it through the MCP
server, receives the message exactly as it was written, and the documentation says it is Markdown.

**Why this priority**: Principle X of the constitution. Writing in Markdown must be possible through
the API, and a program must know what it receives.

**Independent Test**: Write a message through the API and read it back through the API and the MCP
server; the text is unchanged.

**Acceptance Scenarios**:

1. **Given** a message containing Markdown sent through the API, **When** it is read back through the API or the MCP server, **Then** the text is returned exactly as written.

### Edge Cases

- **Messages written before this change.** They were saved as plain text and are now shown through
  the Markdown rules. A message holding characters Markdown gives a meaning to (an asterisk, an
  underscore, a leading `#`) may now read differently. This is accepted; nothing is rewritten.
- **A line break inside a paragraph.** People writing support messages press Enter expecting a new
  line. A single line break is kept as a line break when shown, not joined to the line above.
- **Raw HTML that a person meant as text** (an error page pasted outside a code block). It is shown as
  the characters typed, never interpreted and never silently removed.
- **The administration list of requests**, which shows the last line of each thread. It shows plain
  words with the Markdown syntax removed, never a formatted fragment and never raw markers.
- **The subject of a request.** It stays plain text everywhere. Markdown is not interpreted in it.
- **A link to another page of the instance.** It is a link like any other, opening in the same tab.
- **An email client that shows only text.** The plain text part of the email carries the message as
  written, readable as is.
- **An image or a link in an email.** Neither is active in the email: the reader opens the request to
  follow a link. Nothing is fetched when the email is opened.
- **Headings.** A heading written in a message is shown smaller than the headings of the screen
  around it, so a message can never pose as part of the page.
- **Tables, strikethrough and task lists.** Tables and strikethrough are shown. A task list is shown
  as an ordinary list, its `[ ]` and `[x]` kept as the characters written, so a message never draws a
  form control.

## Requirements *(mandatory)*

### Functional Requirements

**Writing**

- **FR-001**: A message MUST accept Markdown when it is written in any of these places: the new
  request form, the requester's reply in a thread, the administrator's reply, and the administrator's
  internal note.
- **FR-002**: The same MUST hold when the message is written through the API or the MCP server.
- **FR-003**: A message MUST be stored exactly as written. Saving MUST NOT remove, rewrite or escape
  any character, including text between angle brackets, so the author's words can always be shown
  back to them as typed.
- **FR-004**: The length limit of a message MUST remain 20,000 characters, counted on the text as
  written.
- **FR-005**: Every box where a message is written MUST carry one line saying that Markdown is
  accepted, with a link to a page explaining the supported syntax. There is no preview and no
  formatting toolbar.

**Reading**

- **FR-006**: Every message MUST be shown formatted, with the same result, in the requester's thread
  and in the administration space.
- **FR-007**: The supported formatting MUST be: paragraphs, line breaks, bold, italic,
  strikethrough, headings, ordered and unordered lists, nested lists, quotes, inline code, code
  blocks, links, automatic links on a bare web address, tables and horizontal rules. A task list is an
  ordinary list whose `[ ]` and `[x]` are shown as written.
- **FR-008**: A code block MUST show its content exactly as written, without any formatting applied
  inside it, and MUST remain readable on a narrow screen by wrapping its long lines, without making
  the page scroll sideways.
- **FR-009**: Headings inside a message MUST be shown at a level and size below the screen's own
  headings.
- **FR-010**: The emails that carry a message (the request received, the request opened for the
  team, the reply sent, the message added) keep their current treatment: simple formatting may show,
  raw HTML MUST be shown as text, and no link or image in the message MUST be active or fetched. Their
  text part MUST carry the message as written.
- **FR-011**: The line summarising the last message in the administration list MUST show plain words
  with the Markdown syntax removed.
- **FR-012**: The subject of a request MUST remain plain text and MUST NOT be interpreted as Markdown
  anywhere.
- **FR-013**: The formatted message MUST keep the accessibility the rest of the screen has: lists
  announced as lists, headings as headings, links named by their text, and a contrast that meets WCAG 2.2 AA.

**Security**

- **FR-014**: Raw HTML in a message MUST never be interpreted in any place the message is shown. It
  MUST be shown as the characters written.
- **FR-015**: Nothing in a message MUST be able to produce an executable script, an event handler, an
  inline style, a frame, an embedded object, a form, a form control, or a meta or base element in the
  page or the email that shows it.
- **FR-016**: A link MUST be active only when its destination uses `http`, `https` or `mailto`.
  Every other destination MUST be shown as inactive text. The check MUST hold whatever the case,
  encoding, whitespace or control characters used to disguise the scheme.
- **FR-017**: A link leaving the instance MUST open in a new tab, MUST NOT give the destination any
  handle on the page that opened it, MUST NOT tell the destination which page it came from, and MUST
  be marked so that search engines do not follow it.
- **FR-018**: Showing a message MUST NOT make the reader's browser or mail client fetch anything from
  an address chosen by the author. An image MUST be shown as a link to its address, never loaded.
- **FR-019**: A message MUST NOT be able to set an identifier or an attribute that collides with the
  page's own (an `id` an existing script or anchor relies on, a `class` borrowed to imitate a button
  or an alert of the application).
- **FR-020**: The formatted output MUST be produced by one set of rules shared by the two screens and
  the administration list, so the rules cannot drift between them.
- **FR-021**: Formatting a message MUST take a bounded time and a bounded amount of memory for any
  input within the length limit, including inputs built to be slow (deep nesting, long runs of
  markers).
- **FR-022**: The administration space MUST apply every one of these rules to what it shows, with no
  exception granted because the reader is an administrator.
- **FR-023**: The safety rules MUST be proven by automated tests covering at least raw HTML scripts
  and event handlers, dangerous link schemes and a disguised spelling of one, remote images, and
  pathological nesting.

**API and MCP**

- **FR-024**: The API and the MCP server MUST return each message exactly as written. They do not
  return a formatted version.
- **FR-025**: The public API documentation MUST say that message bodies are Markdown, and that a
  client displaying one must format it safely itself, never insert it as HTML.

### Key Entities

- **Support message**: one entry of a support thread: the original request, a reply from the
  requester, a reply from the team, or an internal note. Its text is now Markdown, stored as written,
  and shown through the shared formatting rules.
- **Support request**: the thread itself. Its subject stays plain text.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Zero hostile inputs from the tests execute anything, load anything from elsewhere, or
  produce an active dangerous link, on the two screens or in the emails.
- **SC-002**: The tests hold at least 10 distinct hostile inputs and run on every change.
- **SC-003**: A message of the maximum length built to be as slow as possible is shown in under one
  second.
- **SC-004**: The same message reads identically formatted to the requester and to the administrator.
- **SC-005**: Every message written before the change is still shown in full, with no character lost.
- **SC-006**: Somebody writing a request finds how to format it from the form itself, without leaving
  the page.

## Assumptions

- Internal notes follow the same rules as the other messages, although the user named only the
  request and its replies, so that one thread is never formatted two ways.
- Nothing is migrated. Messages already saved stay as they are and are shown through the new rules.
  Text that the old sanitiser removed at saving is already gone and cannot be recovered.
- The supported subset is the one the Knowledge notes already show, with images shown as links, as
  Knowledge notes already do.
- Headings are kept rather than refused, but held below the page's own headings.
- The emails keep the formatting Laravel already gives them, which escapes HTML and leaves links and
  images inactive. They are not aligned with the screens.
- No Content Security Policy is added. The formatting rules are the defence; a policy would only
  matter if they failed.
- No feature flag. The change replaces plain text with a safe superset of it, and nothing is withheld
  from anybody while it is built.
- Search over support requests, if any, keeps matching on the text as written.

## Clarifications

### Session 2026-11-26

- Q: How should writing help the author? → A: A single line under each box saying Markdown is
  accepted, with a link to the syntax. No preview, no toolbar.
- Q: How far should safety go? → A: Laravel's own Markdown formatting with safe settings, images as
  links, no Content Security Policy, no formatted version in the API, emails left as they are.
- Q: A task list draws a checkbox, which FR-015 forbids. Which one wins? → A: FR-015. A task list
  is shown as an ordinary list with its `[ ]` and `[x]` as written.
- Q: How small is a heading inside a message? → A: The size of the author's name above it, never
  larger, and never smaller than that, so it still reads as a heading next to the body text.
