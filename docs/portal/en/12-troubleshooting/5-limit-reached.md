---
id: troubleshoot.limit-reached
title: I hit a limit
slug: limit-reached
section: troubleshooting
---

# I hit a limit

Monica refuses to add something, and names a limit. Most limits belong to the free offer on monicahq.com. An instance somebody runs on their own server has none of them, except the size of one file and the pace of requests. @doc(reference.limits) lists every number.

Nothing already in your account is removed when you reach a limit. Monica only refuses the next addition.

## The free offer holds 1 vault

You tried to create a second vault. The free offer on monicahq.com holds one. Get full access from **Billing** in your settings, or keep everybody in the vault you have. @doc(vaults.introduction) explains when a second vault helps.

## The free offer holds 30 contacts across every vault

You tried to add a contact beyond the free offer. Partial contacts and the contact that stands for you do not count. You can:

- get full access from **Billing** in your settings;
- add somebody as a @doc(contacts.add, "partial contact") instead, when a name is all you need;
- merge people recorded twice, as @doc(duplicates.merge) shows.

## This file would take the account past the storage it is allowed.

Photos and files count toward the storage of your account: 100 MB on the free offer, and more with full access. Remove photos or files you no longer need, or get full access.

## A file is too large

A single file can be up to 10 MB by default, and a photo of a person up to 5 MB. Make the file smaller before you upload it.

## Too Many Requests

A script, a phone or an assistant sent more than 60 requests in a minute on your behalf. The API and phone sync share this allowance. Wait a minute, then slow the script down. The [API reference](/docs/api) explains how to pace requests.

## When the numbers are different

Whoever runs your Monica can set other limits. When they do, the message you see states the number that applies to you.
