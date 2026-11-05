---
id: troubleshoot.reminder-not-received
title: A reminder did not arrive
description: Work through the checks in order, from the reminder’s date and time zone to your channels, to find why it did not arrive.
slug: reminder-not-received
section: troubleshooting
last_updated: 2026-09-29
---

# A reminder did not arrive

Go through these checks in order. Each one says where to look. @doc(reminders.how-they-work) explains the whole path from a reminder to your inbox.

## 1. Is the reminder there, and active?

Open the contact’s page, then **All of … reminders** at the bottom of the **Reminders** panel. Open the reminder.

- **Its page says it is stopped**, for example **Nothing is sent while the record is deleted** or **This reminder has no date to follow**. Its contact was deleted, or the date it read was cleared or taken off the template. @doc(reminders.answer) lists each case and what to do.
- **The date is not listed yet.** Monica plans the dates of the coming months each night. A reminder you just added for a day far ahead may not show its date yet.

## 2. Was the date already answered?

Once a date is marked **I remembered** or **I forgot**, nothing more is sent for it. Look in the reminder’s **History**.

## 3. Was it the right day and time?

A reminder arrives on its day, at its local time, in its own time zone, and never before. A reminder set for 9:00 in New York arrives at 15:00 in Paris. Open the reminder with **Edit** to see its **Time zone**, as @doc(reminders.create) explains.

## 4. Is a channel switched on and working?

Open your settings and choose **Notifications**.

- **Every channel is off.** Monica records the reminder but sends nothing. Switch one on.
- **A channel says it was switched off.** Nothing got through on it several times in a row, so Monica stopped using it and emailed you. Fix the cause and switch it back on. See @doc(notifications.channels).
- **Choose Send a test** on a channel. If the test arrives, the channel works.

## 5. Did it go out?

In your settings, choose **Logs**, then **Emails sent**. A reminder email is listed under the reminder’s wording. If it is there, the problem is between Monica and your inbox: see @doc(troubleshoot.no-email).

You can also send yourself a copy now: in the vault’s **Reminders**, choose **More** on the reminder, then **Send me a test**.

## 6. Quiet hours and a daily summary

If you set quiet hours or a daily summary through the API, a reminder may wait until the quiet hours end, or arrive inside the summary at the hour you chose.

## For whoever runs the instance

Reminders need two things running all the time:

- **The scheduler**, every minute. It plans the dates each night, and sends what is due every five minutes.
- **A queue worker** serving the `high` and `low` queues.

If the scheduler does not run, no reminder is ever sent, and test messages still work, which makes it easy to miss.
