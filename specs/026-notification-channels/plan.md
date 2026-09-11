# Implementation Plan: More ways for a notification to reach somebody

**Branch**: `026-notification-channels` | **Date**: 2026-09-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/026-notification-channels/spec.md`

## Summary

Delivery today branches on one channel in two places and can only send mail. This makes the channel
the unit: `NotificationChannelEnum` carries every fact about a channel, the three boolean preference
columns become rows in `notification_channels` so a new channel needs no migration, and delivery
hands each notification to Laravel's own notification system on the channel it names. On top of that
sit a notification bar, a preferences screen, per-account credentials with a proof code for text
message, and a retention policy through Laravel's `Prunable`.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13.8

**Primary Dependencies**: three new, all verified to resolve against Laravel 13:
`laravel/slack-notification-channel ^3.10`, `laravel-notification-channels/telegram ^8.0`,
`laravel/vonage-notification-channel ^3.3` (heavy tree: `vonage/client-core`, `lcobucci/jwt`,
`laminas/laminas-diactoros`)

**Storage**: existing database. One new table; two migrations altered rather than added, since the
migrations skill says to do that until production

**Testing**: PHPUnit. `tests/Unit` mirrors the namespace, `tests/Feature` covers controllers and views

**Target Platform**: server-rendered Blade with Alpine.js and Alpine Ajax. Suite passes on SQLite,
MySQL and PostgreSQL. **Project Type**: web application, signed-in screens plus JSON API

**Performance Goals**: the bar loads a bounded page, never the whole history (SC-010); a failing
channel blocks no other (SC-004)
**Constraints**: 31 locales; no `down()`; no `constrained()`; Larastan level 5. **Scale**: 6 channels,
6 user stories, 51 requirements, roughly 40 files

## Constitution Check

*GATE: evaluated before Phase 0, re-evaluated after Phase 1. The design added no folder, no
abstraction beyond the enum and no artifact over its own cap, so the verdicts below are unchanged.*

| Principle | Verdict | Note |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | 9 actions; controllers stay transport |
| II. Simplicity over abstraction | Pass | Facts on the enum, no channel class hierarchy. See research D1 |
| III. Tested before merged | Pass | Every new class gets a test; API coverage tested |
| IV. Laravel conventions | Pass | First-party channels, `Prunable`, `encrypted` cast |
| V. Localized and safe | Pass, costly | New strings across 31 locales via `monica:localize` |
| VI. Nothing changes under anybody's feet | Pass | Retention is a stated policy, not a silent change |
| VII. Stable core | Pass | No new personalisation mechanism |
| VIII. Nothing disappears silently | Pass | VIII permits deletion under an explicit retention policy; FR-048 and FR-049 make it explicit |
| IX. Accessible to everybody | Pass | The bar is new UI and is audited to WCAG 2.2 AA |
| X. Everything goes through the API | Pass | `routes/api.php` exists; API coverage is in scope. See contracts |
| XI. The written record is short | **FAIL** | `spec.md` is 570 lines against a 120-line cap |

Two further discrepancies found, neither blocking:

- The constitution's `app/` folder list names `Mail`, which does not exist, and omits `Notifications`
  and `Listeners`, which do. This plan adds no folder, so it does not widen the gap.
- `app/Models/Notification.php` documents "Nothing here is deleted." The retention policy makes that
  false, so the docblock is corrected in this feature.

## Project Structure

### Documentation (this feature)

`plan.md`, `research.md`, `data-model.md`, `quickstart.md`, `contracts/http.md`. `tasks.md` comes
from `/speckit-tasks`.

### Source code (repository root)

```text
app/Actions/        edited: DeliverNotification, NotifyReminderOccurrence, SendNotificationDigest
                    new: UpdateNotificationChannel, SendChannelProofCode, ConfirmChannelProofCode,
                    SendChannelTestMessage, RecordChannelFailure, MarkNotificationsAsRead
app/Enums/          NotificationChannelEnum: three cases and the per-channel facts
app/Http/Controllers/Api/   NotificationController, NotificationChannelController
app/Http/Controllers/App/   NotificationController, Settings/Account/Notifications/*
app/Jobs/           edited: DeliverDueNotifications, SendNotificationDigests
app/Models/         new: NotificationChannel. edited: Notification (Prunable), NotificationPreference
app/Notifications/  edited: ReminderDue, NotificationDigest. new: ChannelProofCode, ChannelDisabled
app/ViewModels/     new: NotificationBarViewModel, Settings/.../NotificationChannelsViewModel
                    edited: Vaults/ReminderDelivery, ReminderHistory, ReminderRow
config/monica.php   retention, failure threshold, proof code limits
database/migrations/  notification_preferences and its backfill edited; notification_channels added
resources/views/    components/notification-bar.blade.php, app/settings/account/notifications/
routes/             web.php, api.php
tests/              Unit and Feature mirrors of every file above
```

**Structure Decision**: no new folder. Every file lands in a directory the constitution already
fixes, which is why channel facts go on the existing enum rather than into a class per service.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| `spec.md` 570 lines against Principle XI's 120-line cap | The clarification session took the feature from "make delivery channel-agnostic" to six user stories spanning a notification bar, a preferences screen, three outbound integrations and a retention policy. The requirements are each one sentence already; the length is scope, not padding | Compressing the prose does not reach 120 lines at this scope. The real remedy is splitting User Story 5 (Slack, Telegram, text message) into its own feature, which was recommended before planning and not taken. Principle XI allows the cap to be exceeded if the pull request says which and why, and this row is that statement |
| Three new outside dependencies | One per outbound service; none is optional once Q1 named all three | Writing HTTP calls by hand for three services means maintaining three API clients, which is more code and more risk than three maintained packages |
