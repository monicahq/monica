---
id: reference.limits
title: Limits and defaults
description: Look up every number Monica works with, such as password length, link lifetimes and storage, and who can change each one.
slug: limits
section: reference
last_updated: 2026-09-29
---

# Limits and defaults

Every number Monica works with. **Who can change it** says whether it is the same everywhere, or set by whoever runs your Monica, either on the administrator’s configuration screen or in the configuration of the instance. When a screen or an email states a number, that number is the one that applies to you.

## Signing in

| What | Default | Who can change it |
| --- | --- | --- |
| Password length | At least 8 characters | Fixed |
| Remember me | 30 days | Administrator |
| Sign in link | Works once, for 5 minutes | Administrator, who can also switch links off |
| Link to choose a new password | Works for 60 minutes, one new link a minute | Fixed |
| Link to confirm your email address | Works for 60 minutes | Fixed |
| Wrong passwords before a pause | 5 for the same address from the same place | Fixed |
| Requests for a link, a new password, a confirmation or a two factor code | 6 a minute | Fixed |
| Recovery codes | 8 codes of 10 characters, each once | Fixed |
| Confirming it is you, before a sensitive change | Lasts 3 hours | Instance configuration |

## The free plan on monicahq.com

| What | Default | Who can change it |
| --- | --- | --- |
| Vaults | 1 | Administrator |
| Contacts, across every vault | 30, not counting partial contacts and the contact that stands for you | Administrator |
| Storage | 100 MB | Administrator |
| Storage with full access | 10 GB | Administrator |

An instance somebody runs on their own server has none of these limits.

## Files and photos

| What | Default | Who can change it |
| --- | --- | --- |
| One file | Up to 10 MB | Administrator |
| A photo of a person, or your own | JPEG, PNG or WebP, up to 5 MB | Fixed |
| A file detached from its record | Kept 30 days before it is removed | Administrator |

## Reminders and notifications

| What | Default | Who can change it |
| --- | --- | --- |
| Dates planned ahead | 90 days | Administrator |
| Checking for what is due | Every 5 minutes | Fixed |
| Attempts to deliver a notification | 3 | Administrator |
| Failures before a channel is switched off | 5 in a row | Administrator |
| Code to prove a phone number | 6 digits, lasts 10 minutes, 5 tries, 3 sends | Instance configuration |
| Notifications kept in the app | 12 months | Administrator |
| A repeating reminder’s gap | Up to 99 weeks, months or years | Fixed |

## Sharing

| What | Default | Who can change it |
| --- | --- | --- |
| When a sharing link stops working | In 7 days, in 30 days, in 3 months, or never | Fixed |

## Scripts, phones and assistants

| What | Default | Who can change it |
| --- | --- | --- |
| API and phone sync requests | 60 a minute per person, shared | Fixed |
| AI assistant requests | 60 a minute per person on monicahq.com, none on an instance run by somebody else | Fixed |
| Items per page in the API | 25, and at most 100 | Administrator |
| API keys | Never expire | Fixed |

## Languages

Monica is available in 31 languages. See @doc(account.preferences).
