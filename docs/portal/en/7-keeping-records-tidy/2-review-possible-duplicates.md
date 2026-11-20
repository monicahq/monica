---
id: duplicates.review
title: Review possible duplicates
description: See which contacts Monica suggests might be the same person, why it suggested them, and mark the pairs that are not.
slug: review-possible-duplicates
section: keeping-records-tidy
last_updated: 2026-11-20
---

# Review possible duplicates

Monica looks for people in a vault who might be the same person, and lists them for you to decide. It only suggests: nothing is merged until you choose to.

## How Monica finds them

Monica suggests a pair when:

- **Their names are alike.** Two contacts whose names read almost the same, such as "Phoebe Buffay" and "Phoebe Bufay".
- **They share a detail that identifies somebody.** Two contacts with the same email address or the same phone number, whatever their names.

Each pair says why it was suggested, for example **their names are alike** or **they share phoebe.buffay@smellycat.example**.

The contact that stands for you is never suggested.

Monica looks in the background, at three moments: shortly after you add a contact, every night across the whole vault, and whenever you ask it to. The nightly search is also the one that notices a template that has just gained an identifying field. While an import is writing into a vault, Monica waits, and searches that vault once when the import ends.

## Open the list

::::steps
:::step title="Open the people of the vault"
In the vault’s sidebar, choose **People**.
:::

:::step title="Open the duplicates"
Open the menu next to **Add somebody**, then choose **Manage duplicates**. The screen **People who might be the same person** lists every pair under **Possible duplicates**.

::screenshot{label="The People who might be the same person screen, listing Joey Tribbiani and Joseph Tribbiani as a possible duplicate with a Compare them button" src="1-vaults.duplicates.index.webp"}
:::
::::

When there is no pair to review, the screen says **Nothing looks doubled**.

## Look again now

To search without waiting for the night, choose **Look for duplicates**. Monica says **Looking for duplicates.** The search runs in the background, so come back to the screen in a moment to see what it found. During an import into the vault, Monica says **The search waits for the import.** instead, and searches on its own once the import has finished.

## Decide on a pair

For each pair, choose one of two actions:

- **Compare them** if they might be the same person. Monica opens the two records side by side, ready to merge. @doc(duplicates.merge) explains the rest.
- **Two different people** if they are not. Phoebe and her twin sister Ursula share a last name and a face, but they are two people.

:::note
**Two different people** is final. Monica says **This pair will not be raised again**, and it never suggests those two people to each other again.
:::

## Next steps

- @doc(duplicates.merge, "Merge two contacts") that are the same person.
