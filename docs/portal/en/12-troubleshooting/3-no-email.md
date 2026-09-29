---
id: troubleshoot.no-email
title: I am not receiving emails
slug: no-email
section: troubleshooting
last_updated: 2026-09-29
---

# I am not receiving emails

An email you expected from Monica did not arrive: a sign in link, a password link, a confirmation, or a reminder.

## Check on your side

1. **Wait a minute.** Emails can take a moment.
2. **Look in your spam folder**, and in any other folder your mail program sorts messages into.
3. **Check the address.** Open your settings and choose **Profile**. Monica writes to the address shown there, and only that one.
4. **Ask again.** A new link replaces the old one, so open the newest email.

## Check whether Monica sent it

If you can sign in, open your settings, choose **Logs**, and look at **Emails sent**. @doc(account.logs) explains it.

- **The email is listed as Delivered.** Your mail provider accepted it. Look in spam again, or ask your mail provider.
- **It is listed as Bounced.** It could not be delivered. Check the address in your profile.
- **It is listed as On its way.** Monica sent it. Some instances never learn what happened next, so this can stay as it is even for an email that arrived.
- **It is not listed at all.** It was never sent. The next section is for whoever runs your Monica.

## For whoever runs the instance

When no email goes out at all, check these, in order:

1. **A queue worker is running, and serves the `high` queue.** Every email Monica sends waits on the `high` queue, except the test message of a notification channel. Without a worker processing that queue, nothing is sent.
2. **A mail service is configured.** Check the mail settings of the instance, in the environment or on the administrator’s configuration screen.
3. **The mailer is not writing to the log.** With the `log` mailer, emails are written to the application log instead of being sent. That is useful on a development machine, and never what you want in use.

A reminder email also needs the scheduler to run every minute, as @doc(troubleshoot.reminder-not-received) explains.

On monicahq.com, contact us if an email you expect keeps missing.
