---
id: vaults.trash
title: Restore something you deleted
description: Find what you deleted from a vault in its trash, bring it back with everything that went with it, or delete it for good before its 30 days are over.
slug: the-trash
section: vaults
last_updated: 2026-11-25
---

# Restore something you deleted

When you delete something from a vault, it is not gone straight away. It waits 30 days in the vault’s trash, in case you change your mind, and then it is deleted for good.

## What goes to the trash

People, animals and the other records you keep, groups, relationships, life events, reminders, group memberships, notes on a group, and files kept with a group.

Deleting some things takes others with them:

- **Deleting a person** also sends their relationships, their group memberships, their reminders, and the life events nobody else took part in.
- **Deleting a group** also sends its memberships, its notes, its files and its reminders. The people in the group stay.

What goes with something comes back with it.

## Open the trash

::::steps
:::step title="Open the vault’s settings"
In the vault’s sidebar, choose **Vault settings**.
:::

:::step title="Choose the Trash tab"
The tab says how many things wait there.
:::
::::

The newest deletion comes first. Each line says what was deleted, where it belonged, when it was deleted, and how many days it still waits. Hover over or focus the number of days to see the date it is deleted for good. The number is drawn as a warning when three days or fewer are left.

**Show** lets you look at one kind of thing at a time. Under **Everything**, a life event deleted with a person is not listed on its own, because it comes back with that person. Choose **Life events** to see it.

## Restore something

Choose **Restore** on its line. It goes back where it was, and Monica says what came back with it. A person you restore gets back the reminders their deletion stopped. A sharing link that was revoked when they were deleted stays revoked.

### When Restore is greyed out

Some things wait for something else to come back first, and the line says what:

- A life event or a reminder deleted with a person waits for that person. Restore the person instead.
- A relationship waits until both people are back.
- A membership, a note or a file waits until its group is back.
- A record whose type you deleted from the account settings cannot come back.

## Delete something for good

Choose **Delete for good** on its line, then confirm. Whatever was deleted with it goes too. This cannot be undone.

To clear everything at once, choose **Empty the trash**. The confirmation counts what will be deleted.

:::warning
Something deleted for good cannot be restored, by you or by us. A file deleted for good is removed from your storage once nothing else uses it.
:::

## Through the API or an assistant

Everything the Trash tab does can be done through the API and by an assistant connected to Monica: list the trash, restore something, delete it for good, and empty the trash.

## Next steps

- @doc(vaults.settings, "Change a vault’s settings").
- @doc(vaults.delete) to remove a whole vault, which does not go to the trash.
