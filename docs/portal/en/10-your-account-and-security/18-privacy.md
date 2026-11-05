---
id: concepts.privacy
title: What Monica keeps and who can see it
description: See what Monica stores about you and your contacts, what administrators can and cannot see, and what leaves your account.
slug: privacy
section: your-account-and-security
last_updated: 2026-09-29
---

# What Monica keeps and who can see it

What is stored about you and the people you record, and who can reach it.

## Your account is yours alone

An account holds one person. Nobody else signs in to it, and nobody joins your vaults. The people you write about are never told, and have no access to what you wrote. @doc(concepts.model) explains the idea.

## What the administrator of your Monica sees

Every instance of Monica, monicahq.com included, has administrators who look after it: its accounts, its storage and its settings. Their screens show what an account is, never what is inside its vaults:

- **They see:** your name and email address, when the account was created and last signed in to, how you sign in and whether two factor authentication is on, how many vaults and contacts you have, and how much storage you use.
- **They never see through their screens:** your contacts, what you wrote about them, your reminders or your files.

What an administrator does to an account, such as switching it off, is recorded against their name.

## What leaves your account, and only when you choose

- **A sharing link** shows one contact, with what you chose, to whoever holds it. @doc(sharing.what-a-link-shows) lists exactly what.
- **A vCard file** holds one contact, including fields marked sensitive. See @doc(export.vcard).
- **An API key or a connected assistant** acts as you, with your access. See @doc(integrations.overview).

## Fields marked sensitive

A field marked sensitive in a template is hidden on screen until you reveal it. It is left out of sharing links unless you include it, left out of your phone’s address book unless the key allows it, and left out of the columns of the list of people. It is not a lock: anybody signed in to your account can reveal it.

## How secrets are kept

- **Your password** is stored scrambled, so it cannot be read back, even by an administrator.
- **Your two factor pairing and recovery codes** are stored encrypted.
- **Sign in links, sharing links and API keys** are stored scrambled. This is why Monica shows each key and each sharing link only once.

## What Monica records about you

Your @doc(account.logs, "logs") keep every action on your account, with its time and internet address, and a copy of every email sent to you, without its links. You can read them at any time. They are deleted with your account.

## Who is responsible

On monicahq.com, we run the service, and our [terms](https://monicahq.com/terms) and [privacy policy](https://monicahq.com/privacy) apply. On an instance somebody runs on their own server, they hold your data and decide how it is kept and backed up.
