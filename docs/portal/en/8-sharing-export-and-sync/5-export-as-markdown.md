---
id: export.markdown
title: Export a contact as Markdown
description: Download or copy everything you recorded about one contact as a text document, to keep it or hand it to an AI assistant.
slug: export-as-markdown
section: sharing-export-and-sync
last_updated: 2026-11-20
---

# Export a contact as Markdown

Markdown is plain text with a few marks for headings and lists, so it reads well as it is and any AI assistant understands it. Export a contact this way when you want to ask an assistant about somebody, such as what to get Jim for his birthday, or to keep a readable copy of everything you wrote about them.

## Download or copy a contact

::::steps
:::step title="Open the contact"
Open the page of the person you want to export, such as Pam Beesly.
:::

:::step title="Download the file"
Open the menu next to **Edit contact** and choose **Export as Markdown**. Your browser downloads a file named after the contact, such as `pam-beesly.md`.

::screenshot{label="The top of Phoebe Buffay’s page, with the Edit contact button and the menu button beside it" src="1-contacts.show.webp"}
:::

:::step title="Or copy the text"
Choose **Copy as Markdown** instead. The same text is put on your clipboard, ready to paste into a conversation with an assistant. The page says **Copied** once it is there.
:::
::::

## What the document holds

The document holds everything the contact page shows, in the same order:

- the name, the nickname and the other ways the name is written;
- every field that holds a value, under the page and the section it belongs to;
- the relationships, by category;
- the life events, newest first, with who else was there;
- the groups, the active reminders and the loans.

A part with nothing in it is left out. Headings are written in your language, and what you wrote stays exactly as you wrote it. The document is made when you ask for it and is never stored.

:::warning
The document includes fields you marked sensitive. Anybody or anything you give it to can read them, including the company behind an assistant. Remove what you do not want to share before you paste it.
:::

## From a program or an assistant

The same document is available through the API and the MCP server, so an assistant you connected can read a whole contact in one step. Over the API and MCP, sensitive values are only included when the key carries the sensitive fields scope. See the [API reference](/docs/api) and the [MCP server](/docs/mcp).

Every export, whether downloaded, copied or fetched, is written to your account journal.

## Next steps

- @doc(export.vcard, "Export the contact as a vCard") to put it in an address book instead.
- @doc(integrations.overview, "Connect Monica to other tools") to let an assistant read your vaults directly.
