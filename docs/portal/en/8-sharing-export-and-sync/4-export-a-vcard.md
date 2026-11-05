---
id: export.vcard
title: Export a contact as a vCard
description: Download one contact as a .vcf file that any address book can import, to hand it over once.
slug: export-a-vcard
section: sharing-export-and-sync
last_updated: 2026-09-29
---

# Export a contact as a vCard

A vCard is a small file, ending in `.vcf`, that any address book can read: the contacts app on a phone, an email program, or another copy of Monica. Export one when you want to hand a contact over once, or keep a copy of somebody before you delete them.

## Export a contact

::::steps
:::step title="Open the contact"
Open the page of the person you want to export, such as Joey Tribbiani.
:::

:::step title="Export"
Open the menu next to **Edit contact** and choose **Export as vCard**. Your browser downloads a file named after the contact, such as `joey-tribbiani.vcf`.
:::

:::step title="Open it elsewhere"
Open the file on the device or in the program that should receive the contact. Most address books import it straight away.
:::
::::

## What the file holds

The file holds what an address book understands: the name, the nickname, the birthday, the email addresses, the phone numbers, the postal addresses, the website and the photo. Everything else you wrote in Monica, such as notes, relationships and life events, stays in Monica.

:::warning
The file includes fields you marked sensitive. Anybody who receives it can read them. To share only some details, use @doc(sharing.create-link, "a sharing link") instead.
:::

## What is not available

- **One contact at a time.** There is no way yet to export a whole vault or your whole account from the web.
- **No import from a file.** You cannot yet upload a vCard or a spreadsheet to add people from the web.

To bring contacts in from a phone, or keep a phone and a vault in step all the time, set up [CardDAV and CalDAV](/docs/carddav). It works in both directions.

## Next steps

- @doc(integrations.overview, "Connect Monica to other tools") for continuous sync.
