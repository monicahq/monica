---
id: templates.change-many
title: See how your records use templates, and change many at once
description: Count the people, animals or other records of a vault by template and version, see which fields are filled in, and move many records onto another template in one go.
slug: statistics-and-template-changes
section: templates-and-customisation
last_updated: 2026-11-25
---

# See how your records use templates, and change many at once

Every person, animal or other record in a vault follows a template, and a precise version of it. Over time, a vault ends up with records spread across several templates and several versions. The **Statistics** page shows you that spread at a glance, tells you which fields people actually fill in, and lets you move many records onto another template in one decision.

If you have not met templates yet, @doc(templates.introduction) explains what they are, and @doc(templates.versions) explains why a record stays on the version it was made with.

## Open the statistics

Every list of records has a **Statistics** button next to its add button: **Add somebody** on the list of people, **Add Animal** on the list of animals, and so on for every kind of record you created. On a narrow screen, the button moves into the menu at the top of the list.

The page counts every record of that kind in the vault you are in. A search, a filter or a page you chose on the list does not change the figures. The line under the title, such as **Family vault · Non-archived Animals**, reminds you what is counted.

## What the page shows

### Overview

The figures at the top:

- **The number of records**, such as **Animals 18**. Archived records are not in it.
- **Templates in use**: how many templates at least one of those records follows.
- **On older versions**: how many records follow a version of their template other than the one it publishes now. This figure is hidden when it is zero.
- **Archived**, such as **Archived Animals 2**. Archived records are counted here and nowhere else.

For people, a **Partial contacts** figure also appears. Partial contacts count in the total, are shown apart, and are left out of field completion, since they are not filled in yet.

### Templates

One row per template of that kind:

- the template’s name, marked **Default** when new records of that kind start from it in this vault, and **Inactive** when it is turned off but still followed by some records;
- how many records follow it, and their share of the total, such as **14 Animals · 78%**;
- the versions those records are on, such as **v3 · 11 records** and **v2 · 3 records**.

Choose a version to open the list filtered on that template and that version. Choose **View 14 Animals** to open the list filtered on the template alone. The list shows the filters at the top, such as **Template: Pet** and **Version: v2**. Remove one by choosing it, or choose **Clear filters**.

A template nobody follows still has its row, with **0**, so you can see it is on offer.

### Field completion

Pick a template, and optionally one of its versions. Monica lists every field of that template, grouped by section, with how many records fill it in, such as **Microchip number: 5 of 11 · 45%**.

- The second number counts the records that are offered the field. A field added in version 3 is offered only to the records on version 3, and Monica says **Available from v3** under it.
- A record counts once per field, even when it holds several values in it.
- A value set aside by an earlier change of template does not count.
- Choose **Lowest completion first** to see the fields people neglect at the top, or **Form order** to read them as the form shows them.

When nobody follows the template you picked, Monica says **No records use this template yet.**

## Move many records onto another template

Changing the template of one person is explained in @doc(contacts.change-template). When many records need the same change, do it from the statistics instead.

::::steps
:::step title="Pick the template to leave"
On the **Templates** row of the template your records leave, such as **Horse**, open the menu and choose **Change template…**. The menu offers it only when the row has records and another template can take them.
:::

:::step title="Check which records move"
Monica shows the batch, such as **Horse · 4 Animals · Family vault**. It takes every record of that template in this vault that is not archived. To take only the records on one version, choose that version above the count. Choose **View these 4 Animals** to see them in the list.
:::

:::step title="Pick where they go"
Under **Move to**, pick the template the records should follow. Every other template that is turned on and has a published version is offered. The records go to the version it publishes now. Then choose **Preview changes**.
:::

:::step title="Read the preview"
Monica works out what the move would do, without changing anything yet, as described below. Choose **Back** to change your choice.
:::

:::step title="Confirm"
Choose **Move 4 Animals**. The number is the number of records the preview says can move.
:::
::::

Monica then shows the result. A large batch takes a moment: the page shows how far it is and updates on its own. You can leave it and come back.

### Update records to the newest version of their template

When some records of a template are on an older version, its row offers **Update to v3…**, naming the version the template publishes now. It is the same three steps, with the destination already set and only the records on older versions taken. Even a vault with a single template can use it.

### What the preview tells you

The preview names the move, such as **Horse → Pet v3**, says how many records can move, and counts:

- **Records ready to move**;
- **Records with no field values set aside or restored**: their page simply changes shape;
- **Records with field values set aside**: some of their values have no matching field on the new template;
- **Records with field values restored**: values set aside by an earlier change come back, because the new template has a field for them again;
- **Reminders paused** and **Reminders resumed**.

Choose **Details by field** to see each field touched and how many records it concerns, such as **Stable name · 2 Animals**.

**Set aside** means Monica keeps the value but no longer shows it, because the page no longer asks for it. Nothing is deleted. A value set aside comes back on its own as soon as the record follows a template with a matching field again. For a person, you can read these values under **Unused answers**, as @doc(contacts.change-template) explains.

If another change of template is already moving some of the records, the preview lists them and says how many remain in this batch. Those records are left out.

### What happens during the move

Each record is moved exactly as a single contact is: Monica keeps its current state in its version history first, then moves every value to the matching field, sets aside what has no place, and pauses the reminders on what it set aside. You can revert any record from its version history.

A record that changed after the preview, for instance because somebody moved it by hand, is skipped rather than moved onto something nobody previewed. So is a record archived or deleted in the meantime. The others still move.

### The result

The result says how many records moved, were skipped, or failed, such as **3 Animals moved · 1 skipped**, and gives the reason for each record that did not move, such as **This Animal’s template changed after the preview.** The statistics count only the records that actually moved.

Choose **View affected Animals** to open the list showing only the records this change moved, or **Back to statistics**. The filter appears above the list, and you can remove it like any other.

## What you cannot do

- Move records from one vault to another, or turn a person into an animal. A change of template keeps the record where it is and of the kind it is.
- Pick records one by one. A batch is a template, or one version of it, in one vault. A kind of record can hold thousands of records, and picking among them is what the filtered list is for.
- Move records onto an older version of another template. A batch always goes to the version the template publishes now.
