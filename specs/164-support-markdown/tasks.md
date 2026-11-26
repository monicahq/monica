# Tasks: Markdown in support requests

**Input**: `specs/164-support-markdown/` (spec.md, plan.md, research.md, contracts/, quickstart.md)

**Tests**: required by constitution principle III; one layer each (`.claude/rules/testing.md`). No test of
how anything looks. Data from The Office.

## Phase 1: Setup

- [X] T001 Read `.claude/skills/actions/SKILL.md`, the `view-models`, `views`, `components`, `api-docs-writer`, `mcp-development`, `writer`, `changelog` and `translations` skills, and `.claude/rules/components.md`, `mcp.md`, `testing.md`, before writing code

## Phase 2: Foundational (blocks every story)

- [X] T002 Create `app/Helpers/SupportHeadingLevelExtension.php`: an `ExtensionInterface` listening to `DocumentParsedEvent` that sets every `Heading` level to `min(6, level + 3)` (research R3). Class docblock of one sentence saying why, in the shape of `KnowledgeImageLinkExtension`
- [X] T003 Create `app/Helpers/SupportMarkdown.php` (research R2, R7; contracts/rendering.md): `toHtml(?string): string` returns `''` for a blank input, otherwise `Str::markdown()` with `html_input` `escape`, `allow_unsafe_links` `false`, `max_nesting_level` 20, `max_delimiters_per_line` 100, `renderer.soft_break` `"<br />\n"`, and `external_link` as `KnowledgeMarkdown::options()` sets it plus `nofollow` `external`, and the extensions `KnowledgeImageLinkExtension`, `ExternalLinkExtension`, `SupportHeadingLevelExtension`. `plainText(?string): string` strips the tags of `toHtml()`, decodes entities, collapses whitespace to single spaces and trims. Class docblock saying the settings live here so no caller can forget one (depends on T002)
- [X] T004 Create `tests/Unit/Helpers/SupportMarkdownTest.php` (research R9): a `#[DataProvider]` of at least ten hostile inputs (`<script>`, `<img src=x onerror=…>`, `<svg onload=…>`, `<iframe>`, `[x](javascript:alert(1))`, `[x](JaVaScRiPt:alert(1))`, `[x](java&#x09;script:alert(1))`, `[x](data:text/html,…)`, `![x](https://example.com/p.gif)`, `![x](javascript:alert(1))`, a fence whose info string holds `"` and `onclick`), each parsed with `DOMDocument` and asserted to hold no `script`, `img`, `iframe`, `svg`, `style` element, no `on*`, `style` or `id` attribute, and only `href` values starting `http://`, `https://`, `mailto:` or `/`. Separate tests: `#` gives `h4`, `###` gives `h6`; a single line break gives `<br />`; a task box is `disabled`; an external link carries `target="_blank"` and `rel` with `noopener`, `noreferrer`, `nofollow`; `<b>hi</b>` comes out as escaped text; `plainText()` of `**Dwight** _said_` is `Dwight said`; 5,000 nested `>` and 10,000 `*` each render in under one second (depends on T003)

## Phase 3: User Story 1, somebody writes a formatted request and reads it formatted (P1)

**Goal**: the requester's thread shows Markdown. **Independent test**: open a request whose body holds a list, a code block, a link and bold text, and read it back formatted.

- [X] T005 [P] [US1] In `app/Actions/OpenSupportRequest.php` and `app/Actions/AddSupportMessage.php`, store the body as written: drop `TextSanitizer::plainText()` on the body and keep it on the subject (research R1). Add to `tests/Unit/Actions/OpenSupportRequestTest.php` and `tests/Unit/Actions/AddSupportMessageTest.php` one test each that a body holding `<b>Michael</b>` and `a < b` is stored exactly as written
- [X] T006 [US1] Create `resources/views/components/support/message-body.blade.php` (research R5): first line comment, `@var string $markdown`, prints `{!! \App\Helpers\SupportMarkdown::toHtml($markdown) !!}` inside a `div` with the prose classes of `resources/views/app/vaults/knowledge/note.blade.php`, headings sized for `h4` to `h6`, and `pre` with `whitespace-pre-wrap wrap-anywhere` instead of horizontal scrolling (depends on T003)
- [X] T007 [US1] In `resources/views/app/support/_message.blade.php`, replace the `<p … whitespace-pre-wrap>` printing `$message['body']` with `<x-support.message-body :markdown="$message['body']" />`, keeping the current width (depends on T006)
- [X] T008 [US1] Create `tests/Feature/Views/Components/Support/MessageBodyTest.php`: `<script>alert(1)</script>` is rendered escaped, and `- Jim` / `- Pam` renders a list holding both names (depends on T006)

## Phase 4: User Story 2, the support team reads and answers in Markdown (P1)

**Goal**: the administration thread shows the same rendering; replies and notes accept Markdown. **Independent test**: reply with a list and a link, read it on both sides.

- [X] T009 [P] [US2] In `app/Actions/SendSupportReply.php` and `app/Actions/AddSupportNote.php`, store the body as written (research R1); add the same stored-as-written test to `tests/Unit/Actions/SendSupportReplyTest.php` and `tests/Unit/Actions/AddSupportNoteTest.php`
- [X] T010 [US2] In `resources/views/app/admin/support/_message.blade.php`, replace the `<p … whitespace-pre-wrap>` printing `$message['body']` with `<x-support.message-body :markdown="$message['body']" />`, keeping the current width (depends on T006)
- [X] T011 [US2] In `app/ViewModels/Admin/SupportQueueViewModel.php::rows()`, build `lastLine` from `SupportMarkdown::plainText($last->body)`, keeping the `Support: :body` wording for a reply (research R7, FR-011); add to `tests/Unit/ViewModels/Admin/SupportQueueViewModelTest.php` a test that a last message `**Toby** is _back_` gives `Toby is back` (depends on T003)

## Phase 5: User Story 3, nothing written in a message can attack its reader (P1)

**Goal**: the surfaces that are not the helper stay safe now that the body is no longer stripped. **Independent test**: a hostile body is harmless in the email. The helper itself is proven by T004.

- [X] T012 [US3] In `tests/Unit/Notifications/SupportReplySentTest.php`, add a test: a body holding `<script>alert(1)</script>` and `![x](https://example.com/p.gif)` renders (`(string) ->render()`) with the script escaped, no `<script` and no `<img` pointing at example.com; the text part carries the body as written (research R6, FR-010). If it fails, stop and report: the email design in research R6 rests on it
- [X] T013 [US3] Check every other place that prints a support message body: `grep -rn "body" resources/views/app/support resources/views/app/admin/support app/Notifications/Support*.php`. Every print is `{{ }}`, the component of T006, or a notification `line()`; report any `{!! !!}` on a body

## Phase 6: User Story 4, somebody learns that formatting is available (P2)

**Goal**: one line under each box points at the syntax. **Independent test**: each of the four boxes shows the line and its link reaches the section.

- [X] T014 [P] [US4] Add a section `## Format your message` to `docs/portal/en/10-your-account-and-security/19-ask-for-help.md`, after "Send a request": one sentence, then a table of marks in the shape of `docs/portal/en/11-knowledge/3-notes.md` (heading, list, numbered list, quote, bold, code, code block, link), and a line saying raw HTML is shown as typed and images appear as links. Update `last_updated` (`writer` skill)
- [X] T015 [US4] Create `resources/views/components/support/markdown-hint.blade.php` (research R8): first line comment, `@var string|null $id`, a `<p>` in `text-xs text-muted` reading `__('You can format your message with Markdown.')` followed by a link `__('How')` to `PortalCatalogue::pageForReader('account.support')->url().'#format-your-message'`, the link left out when the page is null, as `resources/views/components/help.blade.php` does
- [X] T016 [US4] Place `<x-support.markdown-hint />` under the body box in `resources/views/app/support/new.blade.php` and `resources/views/app/support/show.blade.php`, and under both boxes in `resources/views/app/admin/support/_composer.blade.php`; tie it to its box with `aria-describedby` where the box component allows it (depends on T015)
- [X] T017 [US4] Create `tests/Feature/Views/Components/Support/MarkdownHintTest.php`: the line renders its copy and a link whose `href` ends with `#format-your-message` (depends on T015)

## Phase 7: User Story 5, the API and the MCP server carry the Markdown as written (P2)

**Goal**: the documentation says the body is Markdown. **Independent test**: a message written through the API reads back unchanged.

- [X] T018 [P] [US5] In `resources/docs/api/32-support-requests.php` and `resources/docs/api/33-support-administration.php`, describe `body` (attribute and parameter) with the wording of `contracts/api.md`, and add `resources/docs/api-changelog/<date>.php` for the day it ships, in the shape of `2026-11-25.php` (`api-docs-writer` skill)
- [X] T019 [P] [US5] In `app/Mcp/Tools/CreateSupportRequest.php`, `AddSupportMessage.php`, `SendSupportReply.php`, `AddSupportNote.php`, describe `body` as Markdown rather than plain text
- [X] T020 [US5] In `tests/Feature/Controllers/Api/SupportRequestControllerTest.php`, add one test: a request opened with a Markdown body holding `<b>` reads back with `body` unchanged (`assertJsonPath`)

## Phase 8: Polish

- [X] T021 [P] Write the What's new entry `changelog/<date>-<ulid>-support-markdown.md` (`changelog` skill)
- [X] T022 Run `php artisan monica:localize`, then translate every new string in every locale through Sonnet subagents (`translations` skill)
- [X] T023 Run `vendor/bin/pint --dirty --format agent` and the support tests: `php artisan test --compact --filter=Support` and `tests/Unit/Helpers`
- [X] T024 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff; fix what they confirm
- [ ] T025 Walk `quickstart.md` by hand in the browser
- [X] T026 Run the `definition-of-done` skill

## Dependencies

- T002 to T004 block every story: every surface prints `SupportMarkdown`.
- T006 (the component) blocks T007, T008 and T010.
- US1 and US2 are independent once T006 exists; US3 checks what US1 and US2 leave untouched; US4 and US5 need nothing but Phase 2.
- Phase 8 after the stories; T022 after every wording exists; T026 last.

## Parallel examples

- After T004: T005, T009, T014, T018 and T019 together.
- After T006: T007, T008, T010 together.

## Strategy

MVP is Phase 2 plus US1 and US2: both threads show Markdown safely. US3 confirms the emails, US4 adds
the hint and the syntax page, US5 the documentation.
