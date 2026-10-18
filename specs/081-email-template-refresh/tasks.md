# Tasks: Refreshed Layout for Every Email Monica Sends

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/email-layout.md](contracts/email-layout.md), [quickstart.md](quickstart.md)
**Tests**: required by the constitution (III), one feature test file. Every Blade file below opens with its one line `{{-- --}}` comment (`.claude/rules/code-style.md`). Design values (sizes, colours, spacing) are in `Email Templates.dc.html`, project `4e0e76f6`, read with DesignSync `get_file`.

## Phase 1: Setup

- [X] T001 Render `public/images/logo.svg` to `public/images/email/mark-light.png` at 84 by 75 with `magick -background none -density 300 public/images/logo.svg -resize 84x75 …` (research R4)
- [X] T002 [P] Write a copy of `public/images/logo.svg` to the scratchpad with `#2C2B29` and `#2B2A28` replaced by `#D4D4D8` and `#fff` by `#111113` (the design's inverted mark), render it the same way to `public/images/email/mark-dark.png`

## Phase 2: Foundational

- [X] T003 Create `resources/views/vendor/mail/html/layout.blade.php` from the framework's `layout.blade.php`: keep `lang`, viewport and title; set both `color-scheme` metas to `light dark`; one `<style>` holding `:root { color-scheme: light dark; }`, the phone rules (card 100% wide, 16 px side padding at 600 px and under) and an empty `@media (prefers-color-scheme: dark)` block; body as presentation tables: `email-page` wrapper, a 600 wide centred table, inside it the `email-card` table with rows `$header`, the `email-content` cell holding `Markdown::parse($slot)`, then `$subcopy`; after the card, `$footer` (contract, Regions)
- [X] T004 Create `resources/views/vendor/mail/html/themes/default.css` with the light palette of design 1a only: page `#fafafa`, card white with `#e4e4e7` hairline, 8 px radius, `0 1px 2px rgba(0,0,0,0.04)` shadow; content padding `32px 32px 36px`; `h1` 22/28 600 `#09090b` letter spacing `-0.02em`; first paragraphs 15/24 `#3f3f46`; `strong` `#09090b`; links `#09090b`; system font stack from the design; no media query (research R2)
- [X] T005 Change `resources/views/vendor/mail/html/message.blade.php` so header, subcopy and footer still reach the new layout as slots, keeping its first line comment. Done without a change: it already passes the three slots

## Phase 3: User Story 1, every email in light (P1) MVP

**Independent test**: the confirmation message rendered to a file matches design 1a in a browser in light mode.

- [X] T006 [US1] Create `resources/views/vendor/mail/html/header.blade.php`: an `email-header` row, 20 px by 32 px padding, `#e4e4e7` bottom hairline, linking `$url`, `<img class="email-mark-light" src="{{ asset('images/email/mark-light.png') }}" width="28" height="25" alt="{{ config('app.name') }}">`, the hidden `email-mark-dark` twin (`display:none; mso-hide:all`), and the name 15 px 600 `#09090b`
- [X] T007 [P] [US1] Create `resources/views/vendor/mail/html/button.blade.php`: props `url`, `color` (`primary`, `success`, `error`); a presentation table, cell `email-button email-button-{{ $color }}`, link 14/20 500, padding `10px 18px`, radius 6 px, `#18181b` ground and `#fafafa` text for `primary`, left aligned, `margin-top:24px` equivalent
- [X] T008 [P] [US1] Create `resources/views/vendor/mail/html/subcopy.blade.php`: an `email-strip` row inside the card, `#fafafa` ground, `#e4e4e7` top hairline, padding `18px 32px 20px`, sentence 12.5/19 `#71717a`, link in the monospace stack 12/18 `#27272a` underlined `#d4d4d8`, `word-break:break-all`
- [X] T009 [P] [US1] Create `resources/views/vendor/mail/html/footer.blade.php`: an `email-footer` row outside the card, padding `24px 32px 0`, the 16 by 15 mark pair with `alt=""` (light at 0.55 opacity), the copyright 12/18 `#71717a`
- [X] T010 [US1] In `resources/views/vendor/notifications/email.blade.php`, make the closing sentences and the sign off 14/22 `#52525b` with the design's spacing, and keep the subcopy's link full and wrapping; no string changes (FR-007). Done through a new `closing` mail component (`html/closing.blade.php` and `text/closing.blade.php`), since the plain text part does not strip tags and a bare `<div>` would have leaked into it
- [X] T011 [US1] Create `tests/Feature/Views/Layouts/MailLayoutTest.php` (`declare(strict_types=1)`, `#[Test]`, snake_case, The Office data, `RefreshDatabase`) rendering `VerifyEmail` for Michael Scott: `it_shows_the_heading_once`, `it_shows_the_full_address_when_the_email_has_an_action`, `it_states_the_language_of_the_reader`, `it_names_the_product_on_the_header_mark`; and `ReminderDue` for `it_leaves_out_the_address_when_the_email_has_no_action`

## Phase 4: User Story 2, dark mode where the client allows it (P2)

**Independent test**: the same file, with the operating system in dark mode, matches design 1b.

- [X] T012 [US2] Fill the dark block in `resources/views/vendor/mail/html/layout.blade.php`, every declaration `!important`: page `#09090b`; card `#111113`, hairlines `#27272a`; header name and `h1` `#fafafa`; first paragraphs `#d4d4d8`; closing sentences and sign off `#a1a1aa`; strip `#18181b`, its sentence `#a1a1aa`, its link `#e4e4e7` underlined `#52525b`; footer `#a1a1aa`; `email-button-primary` `#fafafa` ground `#18181b` text; `.email-mark-light` hidden and `.email-mark-dark` shown
- [X] T013 [US2] Repeat the T012 rules under `[data-ogsc]` for text and `[data-ogsb]` for grounds in the same `<style>` (research R3)
- [X] T014 [US2] Add `it_declares_it_supports_light_and_dark` and `it_loads_every_image_from_the_application` (every `src` starts with `config('app.url')`) to `tests/Feature/Views/Layouts/MailLayoutTest.php`

## Phase 5: User Story 3, panel, coloured buttons, bold titles (P3)

**Independent test**: `NewLoginDetected` and `NotificationDigest` rendered to files read correctly in both modes.

- [X] T015 [US3] Create `resources/views/vendor/mail/html/panel.blade.php`: an `email-panel` presentation table, `#fafafa` ground, `#e4e4e7` hairline, radius 6 px, padding 16 px, text 14/22 `#52525b`; dark `#18181b`, `#27272a`, `#d4d4d8` in the layout's dark block
- [X] T016 [P] [US3] Add to `themes/default.css` the `email-button-success` (`#15803d`, `#fafafa`) and `email-button-error` (`#b91c1c`, `#fafafa`) grounds, and to the dark block `#4ade80` and `#f87171` with `#18181b` text (research R6)
- [X] T017 [US3] Add the dark `strong` (`#fafafa`) and in sentence link (`#fafafa`) rules to the dark block in `resources/views/vendor/mail/html/layout.blade.php`
- [X] T018 [US3] Add `it_shows_the_panel_of_a_sign_in_alert` (copy of the `NewLoginDetected` email link panel present) to `tests/Feature/Views/Layouts/MailLayoutTest.php`

## Phase 6: Polish

- [X] T019 Run `php artisan test --compact tests/Feature/Views/Layouts/MailLayoutTest.php tests/Unit/Notifications`, then the whole suite, since every email changes
- [X] T020 Run `php artisan monica:localize --check` (expect nothing new), `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [X] T021 Run the "By eye" section of `quickstart.md` in light, dark and at 375 px through the take-screenshot skill, for `VerifyEmail`, `NewLoginDetected` and `NotificationDigest`, and report the digest's size against Gmail's 102 KB clip
- [X] T022 Report that the "In real clients" section of `quickstart.md` (SC-003, SC-005) needs a person with those clients

## Dependencies

- T001 and T002 come before T006 and T009. T003 to T005 come before every story task.
- US1 is the MVP. US2 needs US1's classes. US3 needs T003 and T007, and its dark rules go in the block T012 creates.
- Parallel: T001 with T002; T007, T008 and T009 after T006; T016 alongside T015. Tests in one file run in sequence.
