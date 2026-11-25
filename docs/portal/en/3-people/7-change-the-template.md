---
id: contacts.change-template
title: Change the template of a contact
description: Move somebody onto another template, see beforehand what happens to everything you wrote about them, and go back if you change your mind.
slug: change-the-template
section: people
last_updated: 2026-11-25
---

# Change the template of a contact

Every contact’s page is shaped by a template. The template decides which tabs, sections and fields the page shows. You can move somebody onto another template at any time. What you wrote about them is never deleted, and this page explains exactly where each piece of it goes.

If you have not met templates yet, @doc(templates.introduction) explains what they are and why two people can have different pages.

## When to change a template

Change a contact’s template when the page no longer fits the person. For example:

- You added Rachel Green with **Basic contact**. She now works with you, and you built a **Colleague** template with a **Work** tab for her desk, her team and her manager. Moving Rachel onto **Colleague** gives her that tab.
- You created somebody with the wrong template by mistake, and the page asks the wrong questions.
- You want to delete a template, and Monica refuses while people still use it. Move them onto another template first.

To move many people at once, such as everybody still on an old template, use the **Statistics** page of the list of people instead, as @doc(templates.change-many) explains.

You do not need to change the template to fix a single answer or add a field to one person. Edit the section instead, as @doc(contacts.edit) explains. And you do not need it to give somebody a newer version of the template they already use: the contact page offers that on its own, as @doc(templates.apply-new-version) explains.

## Change the template

The template is chosen on the **Edit contact** screen, which holds what the template does not decide about a person: their photo, their name, and the template itself.

::::steps
:::step title="Open Edit contact"
Open Rachel’s page and choose **Edit contact**.
:::

:::step title="Open the list of templates"
In the **Template** panel, open the list under **Template used for Rachel**. It shows every template you can use for contacts. A template with several published versions lists each of them, the newest marked **Latest**. The one Rachel uses now is ticked.
:::

:::step title="Pick the template"
Choose **Colleague**. Monica does not change anything yet. A box appears instead, asking **Switch Rachel to the Colleague template?**, and listing exactly what will happen to Rachel’s page.
:::

:::step title="Read what will happen, then confirm"
Read the lists in the box, described below. When you are happy with them, choose **Change template**. To leave everything as it was, choose **Cancel**.
:::
::::

Monica takes you back to Rachel’s page, which now shows the tabs and sections of **Colleague**, and says **Rachel uses the Colleague template now.**

The **Template** panel appears only when there is something else to choose. If you have a single template for contacts, with a single published version, there is nothing to switch to, and the panel is not shown.

## What Monica tells you before you confirm

The box that appears when you pick a template starts with **No data will be lost.** Then it lists, for Rachel and that template only:

- **Information moving to Unused answers.** Each field is named, with the section it belongs to, such as **Allergies** in **Health**. These are the answers the new template has no place for.
- **Information coming back.** Answers that were set aside earlier and that the new template has a place for again.
- **Reminders that stop**, and **reminders that run again**.

A list appears only when it has something in it. When the box shows no list, nothing moves to Unused answers.

Before you pick a template, the line **Changing a template will have some consequences** gives the same rules in general terms. Choose **Read more** to unfold it.

## What happens to each kind of information

Changing the template changes the page, not what you wrote down. Here is where everything goes.

### Answers that have a place on the new template

An answer moves to the matching field of the new template. A field matches when it asks the same question in the same way: the same field, of the same type, such as a date for a date. Monica first looks in the same section, then anywhere else on the template.

Rachel’s birthday, written on **Basic contact**, appears in the birthday field of **Colleague** if that template has one. Its reminder keeps running, untouched.

### Answers with no place on the new template

An answer with no matching field goes to **Unused answers**. It is not deleted. It no longer shows on the page, because the page no longer asks that question, but Monica keeps it.

To see these answers, open the menu next to **Edit contact**, choose **Version history**, then **Unused answers**. The screen, **Answers to questions no longer asked**, lists each one and when it stopped being used.

An unused answer comes back on its own as soon as the contact uses a template with a matching field again, whether you move the person back or add the field to the template.

A file kept in such a field, such as a scanned card, is kept with its answer and comes back with it.

### Reminders

A reminder on a date that goes to **Unused answers** stops, and says why. It does not send anything while it is stopped. It starts again when the date comes back.

A reminder on a date that has a place on the new template keeps running.

### What never changes

Some things belong to the person, not to the template, and a change of template never touches them:

- the name, and the way it reads;
- the photo;
- relationships, life events and groups;
- the vault the person lives in.

### The version history

Before changing anything, Monica keeps a copy of Rachel’s page as it was, in her **Version history**. The history shows which template and which version she used, and from when to when.

## Go back

If the new template was a mistake, you have two ways back.

- **Pick the old template again.** On **Edit contact**, choose the template Rachel used before. The answers that went to **Unused answers** have their fields again, so they come back, and their reminders run again.
- **Revert from the version history.** Open **Version history** from the menu next to **Edit contact**, find the entry of the template she used before, and choose **Revert**. This puts her back on that exact version.

Either way, nothing is lost in the round trip.

You can revert only to a version that is still published, of a template that is still turned on. When that is not the case, the history says why next to the entry.

## Through the API or an assistant

Scripts and AI assistants can do the same thing, with the same rules and the same refusals.

- The [API reference](/docs/api) describes how to preview a change of template and how to make it, one contact at a time.
- A connected assistant previews the change, tells you what will happen, and asks before it changes anything. The [MCP guide](/docs/mcp) explains how to connect one, and @doc(security.assistants) how to manage it.

## What you cannot do yet

- **Change the template of something that is not a person**, such as an animal. Only contacts can change templates for now.
- **Change many people at once.** On the screen, you change one person at a time.
- **Pick a template that is turned off.** A template that is turned off is not offered. A person already on it keeps it until you move them.

## Next steps

- @doc(templates.build-your-own, "Build a template of your own") to give people the page that fits them.
- @doc(templates.versions) to understand what a version of a template is.
- @doc(reminders.how-they-work) to see how reminders follow your answers.
