---
id: integrations.overview
title: Connect Monica to other tools
slug: connect-other-tools
section: sharing-export-and-sync
---

# Connect Monica to other tools

Monica offers three ways for other software to reach what you recorded. They differ in who uses them and what they can do. The guides linked below hold the details; this page helps you pick one.

## Your phone and computer: CardDAV and CalDAV

Use this to see your Monica contacts in the contacts and calendar apps you already use.

- **Contacts** sync in both directions. Each vault appears as an address book. A person you add on your phone appears in Monica, and a change in Monica reaches your phone.
- **Calendars** are read only. Each vault offers a calendar of its recurring dates, such as birthdays, and a list of the reminders waiting for an answer, for apps that support task lists.

Set it up with [CardDAV and CalDAV](/docs/carddav).

## Your own scripts: the API

Use this when you write a program that reads or changes your records, for example to import people from another system. The API answers in JSON, and a script signs in with an API key you create in your settings. A key reaches fields marked sensitive only if you tick **Let this key read the values of fields marked sensitive** when you create it, as @doc(security.api-keys) shows.

Read the [API reference](/docs/api).

## An AI assistant: MCP

Use this to let an assistant such as Claude or ChatGPT look people up, add them, or shape your templates in a conversation. The assistant connects with an API key, or by signing in to Monica on your behalf. You can disconnect an assistant from your settings at any time.

Follow the [MCP guide](/docs/mcp).

## What all three have in common

- **They act as you.** Anything a phone, a script or an assistant does is done in your name, with the same rules as the screens you use.
- **They reach only your account.** A key or a connection never reaches anybody else’s records.
- **They have request limits.** The API and CardDAV together accept 60 requests a minute per person. On monicahq.com, an assistant has its own limit of 60 requests a minute. An instance somebody runs on their own server sets no limit for assistants.

## Which one to choose

| You want to | Use |
| --- | --- |
| See Monica contacts on your phone | CardDAV |
| See birthdays in your calendar app | CalDAV |
| Import people from another system once | The API |
| Ask questions about your contacts in plain words | MCP |
| Hand one contact to somebody | Neither: @doc(export.vcard, "export a vCard") or @doc(sharing.create-link, "share a link") |
