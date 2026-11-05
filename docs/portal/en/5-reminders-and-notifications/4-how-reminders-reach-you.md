---
id: reminders.how-they-work
title: How reminders reach you
description: Learn when a reminder is sent, why it keeps its own time zone, and what can hold it back.
slug: how-reminders-reach-you
section: reminders-and-notifications
last_updated: 2026-09-29
---

# How reminders reach you

When exactly you are told, on which channel, and what can hold a reminder back.

## A reminder and its dates

A reminder is a rule: "Chandler’s birthday, every year on 8 April, at 9:00 New York time". Each day the rule falls on is a date of its own, which you answer separately. A reminder that repeats has many dates, and a reminder that happens once has a single date.

Monica does not keep every future date at once. Each night, it plans the dates that fall in the months ahead, 90 days by default, and adds more as time passes. This is why the vault’s **Reminders** shows the next few months rather than the next ten years.

When you change a reminder’s schedule, Monica cancels the dates it planned and plans new ones from the new rule. Dates you already answered stay as they were.

## On the day

A date is due at its local time, in the reminder’s own time zone. Every few minutes, Monica sends whatever has fallen due, once on each channel you switched on. A reminder for 9:00 therefore arrives at 9:00, or a few minutes after.

Nothing is sent ahead of the day. Monica never warns you a week before a birthday: if you want time to buy a present, add a second reminder a week earlier.

## Why a reminder keeps its own time zone

Each reminder is set in the time zone you chose when you added it, and keeps it. If you move from New York to London, Chandler’s birthday reminder still arrives at 9:00 New York time, which is 14:00 in London. Monica does this so a date tied to a place never drifts because you travelled or changed a setting. Edit the reminder to give it another zone.

## When a channel fails

If a channel does not take a notification, Monica tries again, up to three attempts in all by default. If nothing gets through on that channel several times in a row, five by default, Monica switches the channel off and emails you with the subject **Telegram has been switched off**, or the name of whichever channel failed, and says what came back. Nothing is lost: put the settings right, switch the channel back on, and it carries notifications again. @doc(notifications.channels) shows where.

The numbers on this page are defaults. Whoever runs the Monica you use can change them.

## Quiet hours and a daily summary

Monica can hold notifications during quiet hours, such as the night, and send them when those hours end. It can also fold a day’s notifications into one summary, sent at the hour you choose, instead of one message each.

Both are set through the [API](/docs/api) today. No screen in the app changes them yet.

## What stops a reminder

- **You answer the date.** Anything still waiting to be sent for that date is dropped. @doc(reminders.answer) explains answers.
- **You delete the reminder.** Nothing further is sent.
- **The contact is deleted.** Their reminders stop with them.
- **The date it read disappears.** A birthday reminder stops when the birthday is cleared, and a new one can be built when you write the date again. It also stops when the field leaves the contact’s template, and starts again on its own when the field comes back.
- **No channel is switched on.** Monica keeps recording your reminders, and you see them only when you open the app.

## Next steps

- @doc(notifications.channels, "Choose how you are notified").
- @doc(reminders.create, "Set a reminder").
