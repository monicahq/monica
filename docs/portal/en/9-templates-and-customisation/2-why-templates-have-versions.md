---
id: templates.versions
title: Why templates have versions
slug: why-templates-have-versions
section: templates-and-customisation
---

# Why templates have versions

Why changing a template does not change your contacts, and how they catch up when you want them to.

## A template is published in versions

A template is the shape of a contact’s page: its tabs, the sections on each tab, and the fields in each section. @doc(concepts.model) explains the idea.

You never change a template in place. You work on a **draft**, which no contact uses, and when it is ready you **publish** it. Publishing freezes that version exactly as it is. The next change starts a new draft beside it, which becomes the next version when you publish it.

A template has at most one draft at a time. Its **Version history** lists every version, when it was published, what it holds, and what changed from the one before.

## Contacts stay on their version

Every contact points at one version of one template. When you publish version 2, the contacts on version 1 stay on version 1. Their pages look exactly as they did, and nothing you wrote moves.

A new contact starts from the version its vault names as its default, which stays the same after you publish a newer one. To have new contacts start from version 2, choose it in the vault settings, as @doc(vaults.settings) shows. Contacts already written catch up only when you move them, one by one, with **Apply version 2** on their page. @doc(templates.apply-new-version) shows how.

Say you publish a version 2 of your friends template, with a new **How we met** section. Rachel, written last year, still shows version 1 until you apply version 2 to her. Once you choose version 2 in the vault settings, the people you add from then on start on it.

## Why it works this way

We think that nothing should change under your feet. A template is shared by many contacts, and a change to it would otherwise rearrange all of their pages at once, perhaps hiding something you relied on. Versions make every change deliberate, and let you see its effect on one person before you move the others.

## Nothing you wrote is lost

Moving a contact to another version never deletes an answer:

- **Before the move**, Monica takes a snapshot of the contact, so the contact’s **Version history** can show which versions it used and take it back to an earlier one.
- **An answer to a field the new version no longer has** is set aside, not deleted. It waits under **Answers to questions no longer asked**, and comes back on its own if the field returns. @doc(contacts.edit) shows where to find these answers.

## Next steps

- @doc(templates.build-your-own, "Build your own contact template").
- @doc(templates.apply-new-version, "Move a contact to a new version").
