---
id: 01M7QFH1CJKSDDFYMJ6BM12XRA
title: Contacts cannot be merged while an import writes into their vault
type: fix
area: Contacts
published_at: 2026-11-18T14:03:46Z
---
Merging a contact into one an import was still writing could lose what the merge had moved, if the import was then cancelled or failed, and the merge could not be undone. Monica now waits: while an import writes into a vault, its contacts cannot be merged, and you can merge them once the import has finished.
