---
id: vaults.delete
title: Delete a vault
description: Learn what deleting a vault removes along with it, what to check first, and the steps to delete it for good.
slug: delete-a-vault
section: vaults
last_updated: 2026-11-18
---

# Delete a vault

Remove a vault you no longer need, with everything in it.

:::warning
Deleting a vault deletes every contact and every profile in it, with everything written about them. Nothing in Monica restores a vault, so this cannot be undone.
:::

## What goes with the vault

- **Every contact in it**, with their sections, relationships, life events and reminders. Their reminders stop.
- **Every profile in it**, such as your pets.
- **Every sharing link to its contacts.** Anybody holding one of those links can no longer open it.

Your other vaults are not affected, and neither are your templates, your relationship and life event types, or your account settings.

## Before you delete it

If you want to keep somebody, you can save them first. On their page, open the menu next to **Edit contact** and choose **Export as vCard**. This saves one contact at a time as a file your phone or address book can read.

## Delete the vault

::::steps
:::step title="Open the vault’s settings"
In the vault’s sidebar, choose **Vault settings**.
:::

:::step title="Start the deletion"
In **Delete this vault**, choose **Delete this vault**.

::screenshot{label="The Delete this vault panel, saying that everything filed in the vault is deleted with it and that nothing restores a vault" src="1-vaults.settings.index.webp"}
:::

:::step title="Read the confirmation"
Monica asks **Delete “Work”?**, with the name of your vault, and says how many contacts are deleted with it.
:::

:::step title="Confirm"
Choose **Delete the vault**. To stop, choose **Keep it**.
:::
::::

Monica says **The vault is deleted** and takes you back to **Your vaults**.

## If it was your default vault

When the vault you delete is the default, another of your vaults becomes the default, and new records go there. The confirmation says so before you choose.

## If the button is greyed out

When you have turned off the list of vaults, your default vault cannot be deleted, because it is where Monica takes you when you sign in. **Delete this vault** is greyed out and says so. Turn **Show the list of vaults** back on in **Preferences**, then delete the vault. @doc(account.preferences) explains the setting.

## If it was your only vault

You can delete your last vault. The confirmation warns you that your account will then hold nothing. **Your vaults** says **No vault yet**, and **Make the first one** starts a new vault, which becomes the default.

## Next steps

- @doc(vaults.create, "Create a vault") to start again.
- Read @doc(concepts.model) for how vaults fit with the rest of Monica.
