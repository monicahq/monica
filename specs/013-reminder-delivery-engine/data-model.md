# Data model

The columns are already written down, in [041](../../spec/models/041-reminder-occurrence-done.md),
[042](../../spec/models/042-notification-done.md) and
[043](../../spec/models/043-notification-preference-done.md), and are not repeated here. This
carries only what those files do not say or say wrongly, and it is the list of corrections
FR-045 has to make to them.

## Corrections to the written models

| File | Correction |
| --- | --- |
| 040 Reminder | New column `schedule_revision` (int, default 1), bumped on every change to cadence, interval, moments, local time, zone or the value read. Why: [research.md](research.md). |
| 041 ReminderOccurrence | `generation_key` is `{schedule_revision}:{source_local_date}:{direction}:{amount}:{unit}`, derived from the revision and not from `original_scheduled_for`. |
| 041 ReminderOccurrence | The terminal user state is `dismissed`, with `dismissed_at`. There are no `skipped`, `completed`, `skipped_at` or `completed_at` values because an occurrence is not a task. |
| 041 ReminderOccurrence | The open question on retention stays open. The webhook line stays, marked not done. |
| 042 Notification | New column `attempts` (int, default 0), so a bounded retry is visible in the row rather than only in the queue. |
| 042 Notification | `held_until` is always written, equal to `scheduled_for` when nothing holds it, so the delivery sweep is one comparison instead of two. |
| 043 NotificationPreference | Unchanged. `push_is_enabled` ships with no driver behind it. |
| 001 Account | Gains `hasOne` NotificationPreference. |

## Indexes

| Table | Index | The question it answers |
| --- | --- | --- |
| `reminder_occurrences` | unique (`reminder_id`, `generation_key`) | Has this deadline already been generated? It is what makes two racing workers a no-op. |
| `reminder_occurrences` | (`status`, `scheduled_for`) | Which deadlines are due now? |
| `reminder_occurrences` | (`reminder_id`, `status`, `scheduled_for`) | Which of this rule's deadlines are still ahead? Every reconciliation asks it. |
| `notifications` | unique (`notifiable_type`, `notifiable_id`, `channel`, `user_id`, `scheduled_for`) | Has this person already been told about this due instant, on this channel? The instant lets a postponed occurrence notify again. A digest row leaves the first two null and never collides on any of the drivers. |
| `notifications` | (`held_until`, `sent_at`) | What may go out now? |
| `notification_preferences` | unique (`account_id`) | One row per account. |

## The states a deadline moves through

```text
pending ───────────────► notified
   │                         │
   ├── postpone ─► postponed ◄┘
   │                  │
   └──── dismiss ─────┴────► dismissed   (terminal)

pending / notified / postponed ─────► cancelled   (reason recorded)
```

- Delivery picks up `pending` and `postponed`. `notified` means the notification layer has dealt with the deadline, which is what stops the sweep finding it for ever; whether anything went out is `notified_at`, which stays null on an account that asked for silence.
- Postponing is allowed from `pending`, `notified` and `postponed`, and lands back on `postponed`.
- Dismissing is allowed from `pending`, `notified` and `postponed`, and lands on terminal `dismissed`. It says only that this occurrence must no longer interrupt the user, never that an action was completed.
- Postponing abandons unsent notifications for the former instant; dismissing abandons every unsent notification for the occurrence. Sent rows remain as history, and delivery rejects a notification whose instant no longer matches the occurrence.
- `dismissed` is terminal, and so is `cancelled` for an irreversible reason.
- Restoring reads `status_before_cancellation`, and only for the four reversible reasons.

## Which cancellations reverse

| Reason | Reversed by | Reversible |
| --- | --- | --- |
| `rule_disabled` | restoring the rule | yes |
| `record_archived` | unarchiving | yes |
| `record_deleted` | restoring the record | yes |
| `field_removed` | reattaching the field | yes |
| `value_cleared` | nothing; typing the date again makes a new rule | no |
| `source_changed` | nothing; the replacements are the deadlines now | no |
| `rule_changed` | nothing; same reason | no |

Restoring never touches a deadline whose instant has passed, and never a deadline cancelled for a
different reason than the one being reversed.
