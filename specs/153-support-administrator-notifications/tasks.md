# Tasks: Email the administrators about support requests

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution III. Test data from The Office; `TestCase` already switches support on.
Administrators are made with `Administrator::factory()->create(['user_id' => $user->id])`.

## Phase 1: Foundational

- [X] T001 Add `SupportRequestOpened = 'support_request_opened'` and `SupportMessageAdded = 'support_message_added'` after `SupportRequestReceived` in app/Enums/EmailType.php

## Phase 2: User Story 1, a new request (P1)

**Goal**: each active administrator but the author is emailed when a user opens a request.
**Independent test**: two administrators, a user opens a request, each administrator gets one `SupportRequestOpened`.

- [X] T002 [P] [US1] Create app/Notifications/SupportRequestOpened.php modelled on app/Notifications/SupportRequestReceived.php: constructor takes `reference`, `subject`, `SupportCategoryEnum $category`, `int $accountId`, `authorName`, `body`, `bool $hasAttachments`, `url`; queue `high`; subject, first line, lines (`__($category->shortLabel())`, "From :name, account :id", body), the attachment sentence only when `$hasAttachments`, button "Open the request", outro line per plan.md "The two emails"; `emailType()` returns `EmailType::SupportRequestOpened`
- [X] T003 [P] [US1] Create tests/Unit/Notifications/SupportRequestOpenedTest.php: subject, `actionUrl`, `outroLines`, body and author line in `render()`, `emailType()`, queue `high`, attachment sentence present and absent, category label rendered in `fr_FR` for a French recipient
- [X] T004 [US1] In app/Actions/OpenSupportRequest.php, after the user's copy in `notify()`: return early when `! SupportAvailability::isEnabled()`, then `Notification::send()` a `SupportRequestOpened` to `SupportAvailability::administrators()` without `$this->user`, with `route('admin.support.show', $this->supportRequest)` and `$this->files !== []`; update the class docblock to mention the administrators
- [X] T005 [US1] In tests/Unit/Actions/OpenSupportRequestTest.php add: each active administrator is sent one `SupportRequestOpened`; a deactivated administrator is sent none; with `monica.support_enabled` false no administrator is sent one while the user still gets `SupportRequestReceived`

## Phase 3: User Story 2, a new message (P1)

**Goal**: each active administrator but the author is emailed when a user writes in an existing request.
**Independent test**: one administrator, a user adds a message to a request waiting on the user, the administrator gets one `SupportMessageAdded`.

- [X] T006 [P] [US2] Create app/Notifications/SupportMessageAdded.php as T002 without the category and with `bool $reopened`, adding the reopened sentence only when true; `emailType()` returns `EmailType::SupportMessageAdded`
- [X] T007 [P] [US2] Create tests/Unit/Notifications/SupportMessageAddedTest.php: as T003, with the reopened sentence present and absent instead of the category
- [X] T008 [US2] In app/Actions/AddSupportMessage.php: keep the status before the save in a property, add a `notify()` step after `log()` doing what T004 does with `SupportMessageAdded`, passing `$this->supportRequest->reference`, `$this->supportRequest->subject` and `reopened` true only when the previous status was `SupportRequestStatusEnum::Solved`
- [X] T009 [US2] In tests/Unit/Actions/AddSupportMessageTest.php add: each active administrator is sent one `SupportMessageAdded` with `reopened` false for a request waiting on the user; `reopened` true for a solved request; nothing sent with `monica.support_enabled` false

## Phase 4: User Story 3, no email about their own work (P2)

**Goal**: an administrator writing as a user is not emailed about it. Replies, notes and status changes send nothing by construction (research.md R1).

- [X] T010 [P] [US3] In tests/Unit/Actions/OpenSupportRequestTest.php add: an administrator who opens a request is not sent `SupportRequestOpened`, the other administrator is
- [X] T011 [P] [US3] In tests/Unit/Actions/AddSupportMessageTest.php add the same for `SupportMessageAdded`

## Phase 5: Polish

- [X] T012 Write the administrator-facing changelog entry in changelog/2026-11-24-<ulid>-support-administrator-emails.md (changelog skill), then run tests/Unit/Helpers/ChangelogEntriesTest.php
- [X] T013 Run `php artisan monica:localize` and translate every reported string in lang/*.json through Sonnet subagents (translations skill), then `php artisan monica:localize --check`
- [X] T014 Run `vendor/bin/pint --dirty --format agent`, PHPStan over app/, and `composer test:parallel`
- [X] T015 Run the definition-of-done skill and the checks in specs/153-support-administrator-notifications/quickstart.md by hand

## Dependencies

T001 before T002 and T006. T002 before T004, T006 before T008. T004 before T005 and T010, T008 before T009 and T011. T012 before T013. T012 to T015 last, in order.
US1 and US2 are independent of each other once T001 is done. US3 needs US1 and US2.

## Parallel

- After T001: T002, T003, T006, T007 together.
- After T004 and T008: T010 and T011 together.

## Strategy

MVP is US1 (T001 to T005): the email that tells an administrator somebody started waiting. US2 and US3 follow in the same pull request.
